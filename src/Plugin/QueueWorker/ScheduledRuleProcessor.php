<?php

/**
 * @file
 * Contains \Drupal\rng\Plugin\QueueWorker\ScheduledRuleProcessor.
 */

namespace Drupal\rng\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\rng\Entity\RuleSchedule;

/**
 * Triggers scheduled rules.
 *
 * @QueueWorker(
 *   id = "rng_rule_scheduler",
 *   title = @Translation("Scheduled rule processor"),
 *   cron = {"time" = 60}
 * )
 */
class ScheduledRuleProcessor extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   *
   * @param $data
   *   - rule_component_id: integer: ID of a rule component entity.
   */
  public function processItem($data) {
    /** @var RuleSchedule $rule_schedule */
    $rule_schedule = RuleSchedule::load($data['rule_component_id']);
    $rule_schedule->incrementAttempts();
    $rule_schedule->save();
    if ($component = $rule_schedule->getComponent()) {
      $rule = $component->getRule();
      $event_meta = \Drupal::service('rng.event_manager')
        ->getMeta($rule->getEvent());
      $event_meta->trigger($rule->getTriggerID(), [
        'registrations' => $event_meta->getRegistrations()
      ]);
      $rule_schedule->delete();
    }
  }

}
