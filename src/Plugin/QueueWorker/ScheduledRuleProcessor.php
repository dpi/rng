<?php

namespace Drupal\rng\Plugin\QueueWorker;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rng\EventManagerInterface;
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
class ScheduledRuleProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a ScheduledRuleProcessor object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EventManagerInterface $event_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('rng.event_manager')
    );
  }

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

    if (!$rule_schedule = RuleSchedule::load($data['rule_scheduler_id'])) {
      return;
    }

    $rule_schedule->incrementAttempts();
    $rule_schedule->save();

    if (($component = $rule_schedule->getComponent()) && ($rule = $component->getRule())) {
      $event = $rule->getEvent();
      $event_meta = $this->eventManager->getMeta($event);
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
