<?php

/**
 * @file
 * Contains \Drupal\rng\Entity\Action.
 */

namespace Drupal\rng\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\rng\ActionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\rng\RuleInterface;

/**
 * Defines the event action entity.
 *
 * @ContentEntityType(
 *   id = "rng_action",
 *   label = @Translation("Event Action"),
 *   base_table = "rng_action",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   admin_permission = "administer rng",
 *   handlers = {
 *     "access" = "Drupal\rng\AccessControl\EventAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\rng\Form\ActionForm",
 *       "add" = "Drupal\rng\Form\ActionForm",
 *       "edit" = "Drupal\rng\Form\ActionForm",
 *     },
 *   },
 *   links = {
 *     "canonical" = "/rng/action/{rng_action}/edit",
 *     "edit-form" = "/rng/action/{rng_action}/edit",
 *   }
 * )
 */
class Action extends ContentEntityBase implements ActionInterface {
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
    $this->set('rule', array('entity' => $rule));
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
  public function getActionID() {
    return $this->get('action')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setActionID($action_id) {
    $this->set('action', $action_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->get('configuration')->first()->getValue();
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
  public function execute(array $context) {
    $action_id = $this->getActionID();
    $action_configuration = $this->getConfiguration();

    $manager = \Drupal::service('plugin.manager.action');
    $plugin = $manager->createInstance($action_id, $action_configuration);
    $plugin->execute($context); // @todo context is not standard
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Action ID'))
      ->setDescription(t('The rule ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['rule'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Rule ID'))
      ->setDescription(t('The rule ID.'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'rng_rule');

    // hijack action entity for conditions...
    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('Whether this is an action or condition.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['action'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Action'))
      ->setDescription(t('The action ID.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['configuration'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Configuration'))
      ->setDescription(t('The action configuration.'))
      ->setRequired(TRUE);

    return $fields;
  }
}