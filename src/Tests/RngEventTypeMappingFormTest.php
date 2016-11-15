<?php

namespace Drupal\rng\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\rng\EventManagerInterface;

/**
 * Tests RNG event type mapping form.
 *
 * @group rng
 */
class RngEventTypeMappingFormTest extends RngWebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test'];

  /**
   * The event type for testing.
   *
   * @var \Drupal\rng\EventTypeInterface
   */
  var $eventType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $user = $this->drupalCreateUser(['administer event types']);
    $this->drupalLogin($user);
    $this->eventType = $this->createEventType('entity_test', 'entity_test');
  }

  /**
   * Test default state of the mapping form with a fresh event type.
   */
  function testMappingForm() {
    $this->drupalGet($this->eventType->toUrl('field-mapping'));
    $this->removeWhiteSpace();
    $this->assertRaw('<td>Registration type</td><td>Select which registration types are valid for this event.</td><td>Exists</td>');
    $this->assertRaw('<td>Registration groups</td><td>New registrations will be added to these groups.</td><td>Exists</td>');
    $this->assertRaw('<td>Accept new registrations</td><td></td><td>Exists</td><td></td>');
    $this->assertRaw('<td>Maximum registrations</td><td>Maximum amount of registrations for this event.</td><td>Exists</td><td></td>');
    $this->assertRaw('<td>Reply-to e-mail address</td><td>E-mail address that appears as reply-to when emails are sent from this event. Leave empty to use site default.</td><td>Exists</td>');
    $this->assertRaw('<td>Allow duplicate registrants</td><td>Allows a registrant to create more than one registration for this event.</td><td>Exists</td>');
    $this->assertRaw('<td>Minimum registrants</td><td>Minimum number of registrants per registration.</td><td>Exists</td>');
    $this->assertRaw('<td>Maximum registrants</td><td>Maximum number of registrants per registration.</td><td>Exists</td><td></td>');
  }

  /**
   * Test mapping form when a field does not exist.
   */
  function testMappingFormDeleted() {
    // Delete the field since it was added automatically by EventType::postSave
    $field = FieldConfig::loadByName('entity_test', 'entity_test', EventManagerInterface::FIELD_REGISTRATION_REGISTRANTS_MINIMUM);
    $field->delete();

    $url = $this->eventType->toUrl('field-mapping');
    $this->drupalGet($url);
    $this->removeWhiteSpace();

    $this->assertRaw('<td>Minimum registrants</td><td>Minimum number of registrants per registration.</td><td>Does not exist</td>');
    $this->assertFieldById('edit-table-rng-registrants-minimum-operations-create', 'Create', "Create button exists for 'minimum registrants' field");

    // Test the field is added back.
    $this->drupalPostForm($url, [], t('Create'));
    $this->removeWhiteSpace();
    $this->assertRaw('<td>Minimum registrants</td><td>Minimum number of registrants per registration.</td><td>Exists</td>');
    $this->assertText('Field Minimum registrants added.');
  }

}
