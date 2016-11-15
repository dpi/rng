<?php

namespace Drupal\Tests\rng\Kernel;

use Drupal\simpletest\UserCreationTrait;
use Drupal\rng\EventManagerInterface;

/**
 * Tests ability to register for events..
 *
 * @group rng
 */
class RngRegistrationAccessTest extends RngKernelTestBase {

  use UserCreationTrait {
    createUser as drupalCreateUser;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'entity_test'];

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * @var \Drupal\rng\RegistrationTypeInterface
   */
  protected $registrationType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->eventManager = \Drupal::service('rng.event_manager');

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('registration');
    $this->installEntitySchema('registrant');
    $this->installEntitySchema('rng_rule');
    $this->installEntitySchema('rng_rule_component');
    $this->installEntitySchema('user');
    $this->installConfig('rng');
    $this->installSchema('system', array('sequences'));

    $this->registrationType = $this->createRegistrationType();
    $this->createEventType('entity_test', 'entity_test');
  }

  /**
   * Test register self
   */
  public function testRegisterSelf() {
    $event_meta = $this->createEvent();
    $user1 = $this->drupalCreateUser(['rng register self']);
    $this->setCurrentUser($user1);
    $this->createUserRoleRules([], ['create' => TRUE]);
    $this->assertTrue($event_meta->identitiesCanRegister('user', [$user1->id()]));
  }

  /**
   * Test register self with no default rules.
   */
  public function testRegisterSelfNoDefaultRules() {
    $event_meta = $this->createEvent();
    $user1 = $this->drupalCreateUser(['rng register self']);
    $this->setCurrentUser($user1);
    $this->assertFalse($event_meta->identitiesCanRegister('user', [$user1->id()]));
  }

  /**
   * Test register self rule with no roles.
   *
   * No roles = All roles.
   */
  public function testRegisterSelfRuleNoRoles() {
    $event_meta = $this->createEvent();
    $user1 = $this->drupalCreateUser(['rng register self']);
    $this->setCurrentUser($user1);
    $this->createUserRoleRules([], ['create' => TRUE]);
    $this->assertTrue($event_meta->identitiesCanRegister('user', [$user1->id()]));
  }

  /**
   * Test register self rule a role the user does not have.
   */
  public function testRegisterSelfRuleRoleAlternative() {
    $event_meta = $this->createEvent();
    $role1 = $this->createRole([]);
    $user1 = $this->drupalCreateUser(['rng register self']);
    $this->setCurrentUser($user1);
    $this->createUserRoleRules([$role1 => $role1], ['create' => TRUE]);
    $this->assertFalse($event_meta->identitiesCanRegister('user', [$user1->id()]));
  }

  /**
   * Test register self no permission
   */
  public function testRegisterSelfNoPermission() {
    $event_meta = $this->createEvent();
    // Need to create a dummy role otherwise 'authenticated' is used.
    $role1 = $this->createRole([]);
    $user1 = $this->drupalCreateUser();
    $this->setCurrentUser($user1);
    $this->createUserRoleRules([$role1 => $role1], ['create' => TRUE]);
    $this->assertFalse($event_meta->identitiesCanRegister('user', [$user1->id()]));
  }

  /**
   * Test register self no duplicates.
   */
  public function testRegisterSelfNoDuplicates() {
    $event_meta = $this->createEvent([
      EventManagerInterface::FIELD_ALLOW_DUPLICATE_REGISTRANTS => 0,
    ]);
    $this->createUserRoleRules([], ['create' => TRUE]);
    $user1 = $this->drupalCreateUser(['rng register self']);
    $this->setCurrentUser($user1);

    $this->assertTrue($event_meta->identitiesCanRegister('user', [$user1->id()]));
    $this->createRegistration($event_meta->getEvent(), $this->registrationType, [$user1]);
    $this->assertFalse($event_meta->identitiesCanRegister('user', [$user1->id()]));
  }

  /**
   * Test register self duplicates allowed
   */
  public function testRegisterSelfWithDuplicates() {
    $event_meta = $this->createEvent([
      EventManagerInterface::FIELD_ALLOW_DUPLICATE_REGISTRANTS => 1,
    ]);

    $this->createUserRoleRules([], ['create' => TRUE]);
    $user1 = $this->drupalCreateUser(['rng register self']);
    $this->setCurrentUser($user1);

    $this->assertTrue($event_meta->identitiesCanRegister('user', [$user1->id()]));
    $this->createRegistration($event_meta->getEvent(), $this->registrationType, [$user1]);
    $this->assertTrue($event_meta->identitiesCanRegister('user', [$user1->id()]));
  }

}
