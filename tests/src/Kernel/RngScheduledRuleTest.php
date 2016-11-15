<?php

namespace Drupal\Tests\rng\Kernel;

use Drupal\rng\Entity\Rule;
use Drupal\rng\Entity\RuleComponent;
use Drupal\rng\Entity\RuleSchedule;

/**
 * Scheduled rule entity is synced with parent rule status.
 *
 * @group rng
 */
class RngScheduledRuleTest extends RngKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'entity_test'];

  /**
   * A rule entity for testing.
   *
   * @var \Drupal\rng\RuleInterface
   */
  protected $rule;

  /**
   * A rule component entity for testing.
   *
   * @var \Drupal\rng\RuleComponentInterface
   */
  protected $condition;

  /**
   * A registration type for testing.
   *
   * @var \Drupal\rng\RegistrationTypeInterface
   */
  protected $registrationType;

  /**
   * The event type for testing.
   *
   * @var \Drupal\rng\EventTypeInterface
   */
  protected $eventType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('rng_rule');
    $this->installEntitySchema('rng_rule_component');
    $this->installEntitySchema('rng_rule_scheduler');
    $this->installEntitySchema('courier_template_collection');

    $this->installSchema('system', ['sequences']);

    // Test trait needs.
    $this->registrationType = $this->createRegistrationType();
    $this->eventType = $this->createEventType('entity_test', 'entity_test');

    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = $this->container->get('plugin.manager.action');
    /** @var \Drupal\Core\Condition\ConditionManager $condition_manager */
    $condition_manager = $this->container->get('plugin.manager.condition');

    $event_meta = $this->createEvent();

    $this->rule = Rule::create([
      'event' => array('entity' => $event_meta->getEvent()),
      'trigger_id' => 'rng:custom:date',
    ]);

    $actionPlugin = $action_manager->createInstance('rng_courier_message');
    $action = RuleComponent::create([])
      ->setPluginId($actionPlugin->getPluginId())
      ->setConfiguration($actionPlugin->getConfiguration())
      ->setType('action');
    $this->rule->addComponent($action);

    $conditionPlugin = $condition_manager->createInstance('rng_rule_scheduler');
    $condition = RuleComponent::create()
      ->setPluginId($conditionPlugin->getPluginId())
      ->setConfiguration($conditionPlugin->getConfiguration())
      ->setType('condition');
    $condition->save();

    // Save the ID into config.
    $condition->setConfiguration([
      'rng_rule_component' => $condition->id(),
      // Date in the past.
      'date' => 1234,
    ]);
    $condition->save();

    $this->condition = $condition;
  }

  /**
   * Test rule scheduler entity created for an active rule.
   */
  public function testRuleScheduleCreated() {
    $this->rule
      ->setIsActive(TRUE)
      ->addComponent($this->condition)
      ->save();
    $this->assertEquals(1, count(RuleSchedule::loadMultiple()));
  }

  /**
   * Test rule scheduler entity not created for an inactive rule.
   */
  public function testRuleScheduleNotCreated() {
    $this->rule
      ->setIsActive(FALSE)
      ->addComponent($this->condition)
      ->save();
    $this->assertEquals(0, count(RuleSchedule::loadMultiple()));
  }

  /**
   * Test rule scheduler entity deleted if rule status changes.
   */
  public function testRuleScheduleDeleted() {
    $this->rule
      ->setIsActive(TRUE)
      ->addComponent($this->condition)
      ->save();
    $this->assertEquals(1, count(RuleSchedule::loadMultiple()));

    $this->rule->setIsActive(FALSE)->save();
    $this->assertEquals(0, count(RuleSchedule::loadMultiple()));
  }

}
