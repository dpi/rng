<?php

namespace Drupal\Tests\rng\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\rng\EventManagerInterface;
use Drupal\rng\EventMetaInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Tests the event meta class.
 *
 * @group rng
 * @coversDefaultClass \Drupal\rng\EventMeta
 */
class RngEventMetaTest extends RngKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'entity_test'];

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * An event type for testing.
   *
   * @var \Drupal\rng\EventTypeInterface
   */
  protected $eventType;

  /**
   * Constant representing unlimited.
   *
   * @var \Drupal\rng\EventMetaInterface::CAPACITY_UNLIMITED
   */
  protected $unlimited;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->eventManager = $this->container->get('rng.event_manager');
    $this->eventType = $this->createEventType('entity_test', 'entity_test');
    $this->unlimited = EventMetaInterface::CAPACITY_UNLIMITED;
  }

  /**
   * Tests minimum registrants is unlimited if there is no field value.
   *
   * Including no default field value on the entity level.
   *
   * @covers ::getRegistrantsMinimum
   */
  public function testRegistrantsMinimumNoField() {
    $field = FieldConfig::loadByName('entity_test', 'entity_test', EventManagerInterface::FIELD_REGISTRATION_REGISTRANTS_MINIMUM);
    $field->delete();

    $event = EntityTest::create();
    $event_meta = $this->eventManager->getMeta($event);
    $this->assertSame(1, $event_meta->getRegistrantsMinimum(), 'Minimum registrants is "1" when no field exists.');
  }

  /**
   * Tests minimum registrants is unlimited if there is no field value.
   *
   * @covers ::getRegistrantsMinimum
   */
  public function testRegistrantsMinimumDefaultValue() {
    $field = FieldConfig::loadByName('entity_test', 'entity_test', EventManagerInterface::FIELD_REGISTRATION_REGISTRANTS_MINIMUM);
    $field
      ->setDefaultValue([['value' => 666]])
      ->save();

    $event = EntityTest::create();
    $event_meta = $this->eventManager->getMeta($event);
    $this->assertSame(666, $event_meta->getRegistrantsMinimum(), 'Minimum registrants matches bundle default value.');
  }

  /**
   * Tests minimum registrants is unlimited if there is no field value.
   *
   * @covers ::getRegistrantsMinimum
   */
  public function testRegistrantsMinimumNoDefaultValue() {
    $event = EntityTest::create();
    $event_meta = $this->eventManager->getMeta($event);
    $this->assertSame(1, $event_meta->getRegistrantsMinimum(), 'Minimum registrants matches empty bundle default.');
  }

  /**
   * Tests minimum registrants value when set on event entity.
   *
   * @covers ::getRegistrantsMinimum
   */
  public function testRegistrantsMinimumEventValue() {
    $event = EntityTest::create([
      EventManagerInterface::FIELD_REGISTRATION_REGISTRANTS_MINIMUM => 555,
    ]);
    $event_meta = $this->eventManager->getMeta($event);
    $this->assertSame(555, $event_meta->getRegistrantsMinimum(), 'Minimum registrants matches event field value.');
  }

  /**
   * Tests maximum registrants is unlimited if there is no field value.
   *
   * Including no default field value on the entity level.
   *
   * @covers ::getRegistrantsMaximum
   */
  public function testRegistrantsMaximumNoField() {
    $field = FieldConfig::loadByName('entity_test', 'entity_test', EventManagerInterface::FIELD_REGISTRATION_REGISTRANTS_MAXIMUM);
    $field->delete();

    $event = EntityTest::create();
    $event_meta = $this->eventManager->getMeta($event);
    $this->assertSame($this->unlimited, $event_meta->getRegistrantsMaximum(), 'Maximum registrants is unlimited when no field exists.');
  }

  /**
   * Tests maximum registrants is unlimited if there is no field value.
   *
   * @covers ::getRegistrantsMaximum
   */
  public function testRegistrantsMaximumDefaultValue() {
    $field = FieldConfig::loadByName('entity_test', 'entity_test', EventManagerInterface::FIELD_REGISTRATION_REGISTRANTS_MAXIMUM);
    $field
      ->setDefaultValue([['value' => 666]])
      ->save();

    $event = EntityTest::create();
    $event_meta = $this->eventManager->getMeta($event);
    $this->assertSame(666, $event_meta->getRegistrantsMaximum(), 'Maximum registrants matches bundle default value.');
  }

  /**
   * Tests maximum registrants is unlimited if there is no field value.
   *
   * @covers ::getRegistrantsMaximum
   */
  public function testRegistrantsMaximumNoDefaultValue() {
    $event = EntityTest::create();
    $event_meta = $this->eventManager->getMeta($event);
    $this->assertSame($this->unlimited, $event_meta->getRegistrantsMaximum(), 'Maximum registrants matches empty bundle default.');
  }

  /**
   * Tests maximum registrants value when set on event entity.
   *
   * @covers ::getRegistrantsMaximum
   */
  public function testRegistrantsMaximumEventValue() {
    $event = EntityTest::create([
      EventManagerInterface::FIELD_REGISTRATION_REGISTRANTS_MAXIMUM => 555,
    ]);
    $event_meta = $this->eventManager->getMeta($event);
    $this->assertSame(555, $event_meta->getRegistrantsMaximum(), 'Maximum registrants matches event field value.');
  }

}
