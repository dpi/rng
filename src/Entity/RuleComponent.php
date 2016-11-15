<?php

namespace Drupal\rng\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\rng\RuleComponentInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\rng\RuleInterface;

/**
 * Defines a event rule plugin instance entity: a condition or action.
 *
 * @ContentEntityType(
 *   id = "rng_rule_component",
 *   label = @Translation("Event rule plugin configuration"),
 *   base_table = "rng_rule_component",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   admin_permission = "administer rng",
 *   handlers = {
 *     "access" = "Drupal\rng\AccessControl\EventAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\rng\Form\RuleComponentForm",
 *       "add" = "Drupal\rng\Form\RuleComponentForm",
 *       "edit" = "Drupal\rng\Form\RuleComponentForm",
 *     },
 *   },
 *   links = {
 *     "canonical" = "/rng/component/{rng_rule_component}/edit",
 *     "edit-form" = "/rng/component/{rng_rule_component}/edit",
 *   }
 * )
 */
class RuleComponent extends ContentEntityBase implements RuleComponentInterface {

  /**
   * {@inheritdoc}
   */
  public function getRule() {
    return $this->get('rule')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRule(RuleInterface $rule) {
    $this->set('rule', ['entity' => $rule]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->get('type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->set('type', $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->get('action')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($action_id) {
    $this->set('action', $action_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->get('configuration')->first() ? $this->get('configuration')->first()->getValue() : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->set('configuration', $configuration);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance() {
    if (in_array($this->getType(), ['action', 'condition'])) {
      $manager = \Drupal::service('plugin.manager.' . $this->getType());
      return $manager->createInstance($this->getPluginId(), $this->getConfiguration());
    }
    else {
      throw new \Exception('Invalid RuleComponent type.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array $context) {
    // @todo context is not standard
    $this
      ->createInstance()
      ->execute($context);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Rule Component ID'))
      ->setDescription(t('The rule component ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['rule'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Rule ID'))
      ->setDescription(t('The rule ID.'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'rng_rule');

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('Whether this is an action or condition.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    // @todo Change field ID.
    $fields['action'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Plugin ID'))
      ->setDescription(t('The plugin ID.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['configuration'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Configuration'))
      ->setDescription(t('The component configuration.'))
      ->setRequired(TRUE);

    return $fields;
  }

}
