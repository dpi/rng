<?php

namespace Drupal\Tests\rng\Kernel;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\rng\Entity\EventType;
use Drupal\rng\Entity\Registrant;
use Drupal\simpletest\UserCreationTrait;
use Drupal\rng\Entity\Registration;

/**
 * Tests registration entities.
 *
 * @group rng
 * @coversDefaultClass \Drupal\rng\Entity\Registration
 */
class RngRegistrationEntityTest extends RngKernelTestBase {

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

    $this->eventManager = $this->container->get('rng.event_manager');

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
   * Test creating a registration without event entity throws exception.
   */
  public function testSaveRegistrationWithoutEvent() {
    $this->setExpectedException(EntityStorageException::class);
    $registration = Registration::create([
      'type' => $this->registrationType->id(),
    ]);
    $registration->save();
  }

  /**
   * Test add identity.
   *
   * @covers ::addIdentity
   */
  public function testAddIdentity() {
    $event = $this->createEvent();
    $registration = Registration::create([
      'type' => $this->registrationType->id(),
    ]);
    $registration->setEvent($event->getEvent());

    $user1 = $this->drupalCreateUser();
    $registration
      ->addIdentity($user1)
      ->save();

    /** @var \Drupal\rng\RegistrantInterface[] $registrants */
    $registrants = Registrant::loadMultiple();
    $this->assertEquals(1, count($registrants), 'There is one registrant');

    $registrant = reset($registrants);
    $this->assertEquals($registration->id(), $registrant->getRegistration()->id(), 'Registrant belongs to registration.');
    $this->assertEquals(get_class($user1), get_class($registrant->getIdentity()), 'Identity class is same');
    $this->assertEquals($user1->getEntityTypeId(), $registrant->getIdentity()->getEntityTypeId(), 'Identity entity type is same');
    $this->assertEquals($user1->id(), $registrant->getIdentity()->id(), 'Identity ID is same');
  }

  /**
   * Test creating a registration for an event type that exceeds 32 characters.
   */
  public function testLongEventTypeRegistration() {
    $event_type = $this->createEventTypeBase([
      'bundle' => 'event_type_over_32_characters',
    ]);
    $event = $this->createEvent();
    $registration = Registration::create([
      'type' => $event_type->id(),
    ]);
    $registration->setEvent($event->getEvent());
    $user1 = $this->drupalCreateUser();
    $registration
      ->addIdentity($user1)
      ->save();
  }

  /**
   * Create a event type with only required info.
   *
   * @param array $values
   *   Default values to use when creating the event type.
   *
   * @return \Drupal\rng\EventTypeInterface
   *   An new event type entity.
   */
  protected function createEventTypeBase($values = []) {
    $event_type = EventType::create($values + [
        'id' => $this->randomMachineName(33),
        'label' => $this->randomMachineName(),
        'entity_type' => 'entity_test',
        'bundle' => 'entity_test',
      ]);
    return $event_type;
  }

}
