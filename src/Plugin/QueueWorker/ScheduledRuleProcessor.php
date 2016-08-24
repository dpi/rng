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
   * @param integer $data['rule_scheduler_id']
   *   ID of a rule component entity.
   */
  public function processItem($data) {
    if (!isset($data['rule_scheduler_id'])) {
      return;
    }

    /** @var \Drupal\rng\EventManagerInterface $event_manager */
    $event_manager = \Drupal::service('rng.event_manager');

    $rule_schedule = RuleSchedule::load($data['rule_scheduler_id']);
    $rule_schedule->incrementAttempts();
    $rule_schedule->save();

    if (($component = $rule_schedule->getComponent()) && ($rule = $component->getRule())) {
      $event = $rule->getEvent();
      $event_meta = $event_manager->getMeta($event);
      $context = [
        'event' => $event,
        'registrations' => $event_meta->getRegistrations()
      ];

      foreach ($rule->getActions() as $action) {
        $action->execute($context);
      }

      $rule_schedule->delete();
    }
  }

}
