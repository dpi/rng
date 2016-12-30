<?php

namespace Drupal\rng;

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rng\Entity\RuleSchedule;

/**
 * RNG Cron.
 */
class RngCron {

  /**
   * Queue for rule scheduler.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $ruleSchedulerQueue;

  /**
   * Storage for rule scheduler entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $ruleSchedulerStorage;

  /**
   * Constructs a new RngEntityModel object.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(QueueFactory $queue_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->ruleSchedulerQueue = $queue_factory->get('rng_rule_scheduler', FALSE);
    $this->ruleSchedulerStorage = $entity_type_manager->getStorage('rng_rule_scheduler');
  }

  /**
   * React to Drupal `hook_cron` hooks.
   *
   * @see hook_cron();
   */
  public function hook_cron() {
    $this->scheduleRules();
    $this->deleteScheduleRules();
  }

  /**
   * Add scheduled rules to be executed to the queue.
   */
  protected function scheduleRules() {
    $ids = $this->ruleSchedulerStorage
      ->getQuery()
      ->condition('trigger_date', time(), '<=')
      ->condition('in_queue', 0, '=')
      ->condition('attempts', RuleSchedule::ATTEMPTS_MAX, '<=')
      ->execute();

    /** @var \Drupal\rng\RuleScheduleInterface[] $rule_schedules */
    $rule_schedules = $this->ruleSchedulerStorage->loadMultiple($ids);

    foreach ($rule_schedules as $rule_schedule) {
      $data = ['rule_scheduler_id' => $rule_schedule->id()];
      if ($this->ruleSchedulerQueue->createItem($data)) {
        $rule_schedule->setInQueue(TRUE)->save();

        // De-activate the rule once it is queued.
        if ($component = $rule_schedule->getComponent()) {
          if ($rule = $component->getRule()) {
            $rule->setIsActive(FALSE)->save();
          }
        }
      }
    }
  }

  /**
   * Delete scheduled rules which have had too many attempts.
   */
  protected function deleteScheduleRules() {
    $ids = $this->ruleSchedulerStorage
      ->getQuery()
      ->condition('attempts', RuleSchedule::ATTEMPTS_MAX, '>')
      ->execute();

    /** @var \Drupal\rng\RuleScheduleInterface[] $rule_schedules */
    $rule_schedules = $this->ruleSchedulerStorage->loadMultiple($ids);

    $this->ruleSchedulerStorage->delete($rule_schedules);
  }

}
