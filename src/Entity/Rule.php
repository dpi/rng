<?php

namespace Drupal\rng\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\rng\RuleInterface;
use Drupal\rng\RuleComponentInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the event rule entity.
 *
 * @ContentEntityType(
 *   id = "rng_rule",
 *   label = @Translation("Event Rule"),
 *   base_table = "rng_rule",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   handlers = {
 *     "access" = "Drupal\rng\AccessControl\EventAccessControlHandler",
 *     "list_builder" = "\Drupal\rng\Lists\RuleListBuilder",
 *     "form" = {
 *       "delete" = "Drupal\rng\Form\RuleDeleteForm",
 *     },
 *   },
 *   admin_permission = "administer rng",
 *   links = {
 *     "delete-form" = "/rng/rule/{rng_rule}/delete"
 *   }
 * )
 */
class Rule extends ContentEntityBase implements RuleInterface {

  /**
   * Internal cache of components to associate with this rule when it is saved.
   *
   * @see \Drupal\rng\RuleInterface->addComponent()
   *
   * @var \Drupal\rng\RuleComponentInterface[]
   */
  protected $components_unsaved = [];

  /**
   * {@inheritdoc}
   */
  public function getEvent() {
    return $this->get('event')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTriggerID() {
    return $this->get('trigger_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsActive($is_active) {
    $this->set('status', $is_active);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditions() {
    $ids = \Drupal::entityQuery('rng_rule_component')
      ->condition('rule', $this->id(), '=')
      ->condition('type', 'condition', '=')
      ->execute();

    return array_merge(
      $ids ? RuleComponent::loadMultiple($ids) : [],
      array_filter($this->components_unsaved, function ($component) {
        return $component->getType() == 'condition';
      })
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getActions() {
    $ids = \Drupal::entityQuery('rng_rule_component')
      ->condition('rule', $this->id(), '=')
      ->condition('type', 'action', '=')
      ->execute();

    return array_merge(
      $ids ? RuleComponent::loadMultiple($ids) : [],
      array_filter($this->components_unsaved, function ($component) {
        return $component->getType() == 'action';
      })
    );
  }

  /**
   * {@inheritdoc}
   */
  public function addComponent(RuleComponentInterface $component) {
    $this->components_unsaved[] = $component;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluateConditions($context_values = []) {
    $success = 0;
    $conditions = $this->getConditions();
    // Counts successfully loaded condition plugins:
    $count = 0;

    foreach ($conditions as $component) {
      if (($condition = $component->createInstance()) !== NULL) {
        $count++;
      }

      $context_definitions = ($condition->getContextDefinitions());
      foreach ($context_values as $name => $value) {
        if (isset($context_definitions[$name])) {
          $condition->setContextValue($name, $value);
        }
      }

      if ($condition->evaluate()) {
        $success++;
      }
      else {
        // Cancel evaluating remaining conditions.
        return FALSE;
      }
    }

    return ($success == count($conditions)) && ($count == count($conditions));
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    foreach ($this->components_unsaved as $k => $component) {
      $component
        ->setRule($this)
        ->save();
      unset($this->components_unsaved[$k]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    $component_storage = \Drupal::entityTypeManager()->getStorage('rng_rule_component');

    /** @var \Drupal\rng\RuleInterface $rule */
    foreach ($entities as $rule) {
      // Delete associated rule components.
      $ids = $component_storage->getQuery()
        ->condition('rule', $rule->id())
        ->execute();
      $components = $component_storage->loadMultiple($ids);
      $component_storage->delete($components);
    }

    parent::preDelete($storage, $entities);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Rule ID'))
      ->setDescription(t('The rule ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['event'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Event'))
      ->setDescription(t('Select event to associate with this rule.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['trigger_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Trigger'))
      ->setDescription(t('The trigger ID for this rule.'))
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time that the rule was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The last time the rule was edited.'));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('Whether this rule should run if the trigger is used. 0=disabled, 1=active.'))
      ->setDefaultValue(FALSE)
      ->setRequired(TRUE);

    return $fields;
  }

}
