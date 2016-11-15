<?php

namespace Drupal\Tests\rng\Kernel;

use Drupal\simpletest\UserCreationTrait;
use Drupal\rng\Entity\EventType;

/**
 * Tests event type entities.
 *
 * @group rng
 * @coversDefaultClass \Drupal\rng\Entity\EventType
 */
class RngEventTypeEntityTest extends RngKernelTestBase {

  /**
   * Test getting all identity type form modes.
   *
   * @covers ::getIdentityTypeEntityFormModes
   */
  public function testGetIdentityTypeEntityFormModes() {
    $people_type = [
      'entity_type' => $this->randomMachineName(),
      'bundle' => $this->randomMachineName(),
      'entity_form_mode' => $this->randomMachineName(),
    ];
    $values['people_types'][] = $people_type;
    $event_type = $this->createEventTypeBase($values);

    $result = $event_type->getIdentityTypeEntityFormModes();
    $this->assertEquals($people_type['entity_form_mode'], $result[$people_type['entity_type']][$people_type['bundle']]);
  }

  /**
   * Test getting all identity type form modes when no defaults set.
   *
   * @covers ::getIdentityTypeEntityFormModes
   */
  public function testGetIdentityTypeEntityFormModesNoDefaults() {
    $values['people_types'][] = [
      'entity_type' => $this->randomMachineName(),
      'bundle' => $this->randomMachineName(),
    ];
    $event_type = $this->createEventTypeBase($values);
    $result = $event_type->getIdentityTypeEntityFormModes();
    $this->assertEquals([] , $result);
  }

  /**
   * Test default registrant type defaults.
   *
   * @covers ::setDefaultRegistrantType
   * @covers ::getDefaultRegistrantType
   */
  public function testGetDefaultRegistrantType() {
    $event_type = $this->createEventTypeBase();

    $registrant_type = $this->randomMachineName();
    $event_type->setDefaultRegistrantType($registrant_type);

    $this->assertEquals($registrant_type, $event_type->getDefaultRegistrantType());
  }

  /**
   * Test getting default registrant type defaults set.
   *
   * @covers ::getDefaultRegistrantType
   */
  public function testGetDefaultRegistrantTypeDefault() {
    $event_type = $this->createEventTypeBase();
    $result = $event_type->getDefaultRegistrantType();
    $this->assertNull($result);
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
      'id' => $this->randomMachineName(),
      'label' => $this->randomMachineName(),
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    return $event_type;
  }

}
