<?php

/**
 * @file
 * Contains \Drupal\rng\Tests\EventAccessTest.
 */

namespace Drupal\rng\Tests;

use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\rng\Entity\EventType;
use Drupal\rng\EventManagerInterface;
use Drupal\rng\Entity\Rule;
use Drupal\rng\Entity\RuleComponent;

/**
 * Tests event access.
 *
 * @group rng
 */
class EventAccessTest extends RNGTestBase {

  public static $modules = ['block', 'entity_test'];

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * @var \Drupal\rng\RegistrationTypeInterface
   */
  var $registration_type;

  /**
   * @var \Drupal\rng\EventTypeInterface
   */
  var $event_type;

  /**
   * Creates an event type config.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   An entity type.
   *
   * @return \Drupal\rng\EventTypeInterface
   *   An event type config.
   */
  function createEventTypeNG($entity_type_id, $bundle) {
    $event_type = EventType::create([
      'label' => 'Event Type A',
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'mirror_operation_to_event_manage' => 'update',
    ]);
    $event_type->save();
    \Drupal::service('router.builder')->rebuildIfNeeded();
    return $event_type;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');

    $this->event_type = $this->createEventTypeNG('entity_test', 'entity_test');
    $this->registration_type = $this->createRegistrationType();
    $this->eventManager = \Drupal::service('rng.event_manager');
  }

  /**
   * Test registrant settings.
   */
  function testComponentAccessCache() {
    $event = EntityTest::create([
      EventManagerInterface::FIELD_REGISTRATION_TYPE => $this->registration_type->id(),
      EventManagerInterface::FIELD_STATUS => TRUE,
    ]);
    $event->save();

    $register_link = Url::fromRoute('rng.event.entity_test.register.type_list', [
      'entity_test' => $event->id(),
    ]);
    $register_link_str = $register_link->toString();

    $event_meta = $this->eventManager->getMeta($event);
    $this->assertEqual(0, count($event_meta->getRules(NULL, FALSE, TRUE)), 'There are zero rules');

    // Set rules via API to set a baseline.
    $rule = Rule::create([
      'event' => ['entity' => $event],
      'trigger_id' => 'rng_event.register',
      'status' => TRUE,
    ]);

    $component = RuleComponent::create()
      ->setType('condition')
      ->setPluginId('rng_user_role')
      ->setConfiguration(['roles' => []]);
    $rule->addComponent($component);

    $component = RuleComponent::create()
      ->setType('action')
      ->setPluginId('registration_operations')
      ->setConfiguration(['operations' => ['create' => FALSE]]);
    $rule->addComponent($component);

    $rule->save();
    \Drupal::service('router.builder')->rebuildIfNeeded();

    $user_registrant = $this->drupalCreateUser(['rng register self', 'view test entity', 'administer entity_test content']);
    $roles = $user_registrant->getRoles(TRUE);

    $this->drupalLogin($user_registrant);
    $this->drupalGet($event->toUrl());
    // Register tab is cached, ensure it is missing.
    $this->assertNoLinkByHref($register_link_str);
    $this->drupalGet($register_link);
    $this->assertResponse(403);

    $user_manager = $this->drupalCreateUser(['administer entity_test content']);
    $this->drupalLogin($user_manager);

    // Set conditions so registrant user can register
    // Use UI because component form should invalidate tags.
    $conditions = $rule->getConditions();
    $edit = ['roles[' . $roles[0] . ']' => TRUE];
    $this->drupalPostForm($conditions[0]->toUrl(), $edit, t('Save'));
    $actions = $rule->getActions();
    $edit = ['operations[create]' => TRUE];
    $this->drupalPostForm($actions[0]->toUrl(), $edit, t('Save'));

    $this->drupalLogin($user_registrant);
    $this->drupalGet($event->toUrl());
    // Register tab is cached, ensure it is exposed.
    // If this fails, then the register tab is still cached to previous rules.
    $this->assertLinkByHref($register_link_str);
    $this->drupalGet($register_link);
    $this->assertResponse(200);
  }

}
