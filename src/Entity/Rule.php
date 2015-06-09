<?php

/**
 * @file
 * Contains \Drupal\rng\Entity\Rule.
 */

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
   * @var array
   */
  protected $components_unsaved = [
    'action' => [],
    'condition' => [],
  ];

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
  public function getConditions() {
    $action_ids = \Drupal::entityQuery('rng_rule_component')
      ->condition('rule', $this->id(), '=')
      ->condition('type', 'condition', '=')
      ->execute();
    return entity_load_multiple('rng_rule_component', $action_ids) + $this->components_unsaved['condition'];
  }

  /**
   * {@inheritdoc}
   */
  public function getActions() {
    $action_ids = \Drupal::entityQuery('rng_rule_component')
      ->condition('rule', $this->id(), '=')
      ->condition('type', 'action', '=')
      ->execute();
    return entity_load_multiple('rng_rule_component', $action_ids) + $this->components_unsaved['action'];
  }

  /**
   * Add components to the rule.
   *
   * Components are not saved until the rule is saved.
   *
   * @param string $type
   *   The type of component. Possible values: 'action' or 'condition'.
   * @param \Drupal\rng\RuleComponentInterface $component
   *   The rule component entity.
   */
  public function addComponent($type, RuleComponentInterface $component) {
    $this->components_unsaved[$type][] = $component;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluateConditions($context_values = []) {
    $success = 0;
    $conditions = $this->getConditions();
    // Counts successfully loaded condition plugins:
    $count = 0;

    foreach ($conditions as $condition_storage) {
      if (($condition = $condition_storage->createInstance()) !== NULL) {
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

    // Will fail if there are no conditions.
    return $count && ($success == count($conditions)) && ($count == count($conditions));
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    foreach ($this->components_unsaved as $components) {
      foreach ($components as $component) {
        /** @var \Drupal\rng\RuleComponentInterface $component */
        $component->setRule($this);
        $component->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    $component_storage = \Drupal::entityManager()->getStorage('rng_rule_component');

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

    return $fields;
  }

}
