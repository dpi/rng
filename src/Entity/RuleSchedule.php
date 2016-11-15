<?php

namespace Drupal\rng\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\rng\RuleScheduleInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the rule scheduler entity.
 *
 * @ContentEntityType(
 *   id = "rng_rule_scheduler",
 *   label = @Translation("Rule scheduler"),
 *   base_table = "rng_rule_scheduler",
 *   entity_keys = {
 *     "id" = "id",
 *   }
 * )
 */
class RuleSchedule extends ContentEntityBase implements RuleScheduleInterface {

  /**
   * Maximum number of trigger attempts before giving up.
   */
  const ATTEMPTS_MAX = 5;

  /**
   * {@inheritdoc}
   */
  public function getComponent() {
    return $this->get('component')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getDate() {
    return $this->get('trigger_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDate($date) {
    $this->set('trigger_date', $date);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInQueue() {
    return $this->get('in_queue')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setInQueue($in_queue) {
    $this->set('in_queue', $in_queue);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttempts() {
    return $this->get('attempts')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function incrementAttempts() {
    $this->set('attempts', $this->getAttempts() + 1);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Rule Schedule ID'))
      ->setDescription(t('The rule schedule ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    // @todo: add unique constraint.
    $fields['component'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Rule Component ID'))
      ->setDescription(t('The owner rule component ID.'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'rng_rule_component');

    $fields['trigger_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Execution date'))
      ->setDescription(t('The date the schedule should be added to the queue.'));

    $fields['in_queue'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('In queue'))
      ->setDescription(t('Whether to this schedule entry has been added to the queue.'))
      ->setDefaultValue(0)
      ->setRequired(TRUE);

    $fields['attempts'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Attempt count'))
      ->setDescription(t('Number of times this scheduled rule has run.'))
      ->setDefaultValue(0)
      ->setRequired(TRUE);

    return $fields;
  }

}
