<?php

/**
 * @file
 * Contains \Drupal\rng\Entity\Rule.
 */

namespace Drupal\rng\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\rng\RuleInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityInterface;

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
  public function delete() {
    $action_ids = \Drupal::entityQuery('rng_action')
      ->condition('rule', $this->id(), '=')
      ->execute();
    entity_delete_multiple('rng_action', $action_ids);
    parent::delete();
  }

  /**
   * {@inheritdoc}
   */
  public function getActions() {
    $action_ids = \Drupal::entityQuery('rng_action')
      ->condition('rule', $this->id(), '=')
      ->execute();
    return entity_load_multiple('rng_action', $action_ids);
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