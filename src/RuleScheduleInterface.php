<?php

namespace Drupal\rng;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a rule scheduler entity.
 */
interface RuleScheduleInterface extends ContentEntityInterface {

  /**
   * Get associated rule component.
   *
   * @return \Drupal\rng\RuleComponentInterface
   *   A rule component entity.
   */
  public function getComponent();

  /**
   * Execution date as a unix timestamp.
   *
   * @return int
   *   A timestamp.
   */
  public function getDate();

  /**
   * Set execution date.
   *
   * @param int $date
   *   A unix timestamp for when to execute the rule.
   *
   * @return \Drupal\rng\RuleScheduleInterface
   *   Returns rule schedule for chaining.
   */
  public function setDate($date);

  /**
   * Get if rule schedule is in queue.
   *
   * @return boolean $in_queue
   *   Whether the rule is in the queue for execution.
   */
  public function getInQueue();

  /**
   * Set if the rule schedule is added to the queue.
   *
   * @param bool $in_queue
   *   Whether the rule has been added to the queue for execution.
   *
   * @return \Drupal\rng\RuleScheduleInterface
   *   Returns rule schedule for chaining.
   */
  public function setInQueue($in_queue);

  /**
   * Returns number of times the rule has been triggered while in the queue.
   *
   * The rule will attempt to execute until success, or reached maximum attempt
   * cap.
   *
   * @return int
   *   Number of attempts.
   */
  public function getAttempts();

  /**
   * Increment number of attempts.
   *
   * Attempt count is incremented before rule execution.
   *
   * @return \Drupal\rng\RuleScheduleInterface
   *   Returns rule schedule for chaining.
   */
  public function incrementAttempts();

}
