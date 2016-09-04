<?php


namespace Drupal\rng\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\rng\Entity\Registrant;
use Drupal\rng\Entity\Registration;

/**
 * Tests registrant routes.
 *
 * @group rng
 */
class RegistrantRouteTest extends RNGTestBase {

  /**
   * @inheritdoc
   */
  public static $modules = ['block', 'entity_test'];

  /**
   * The registration type for testing.
   *
   * @var \Drupal\rng\RegistrationTypeInterface
   */
  var $registrationType;

  /**
   * The event type for testing.
   *
   * @var \Drupal\rng\EventTypeInterface
   */
  var $eventType;

  /**
   * The registrant for testing.
   *
   * @var \Drupal\rng\RegistrantInterface
   */
  var $registrant;

  /**
   * Name of the test field attached to registrant entity.
   *
   * @var string
   */
  var $registrantTestField;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->eventType = $this->createEventTypeNG('entity_test', 'entity_test');
    $this->registrationType = $this->createRegistrationType();

    $event_name = $this->randomString();
    $event_meta = $this->createEvent([
      'name' => $event_name,
    ]);

    $registration = $this->createRegistration($event_meta->getEvent(), $this->registrationType->id());
    $user = $this->drupalCreateUser();
    $registration->addIdentity($user)->save();

    $registrant_ids = $registration->getRegistrantIds();
    $registrant_id = reset($registrant_ids);
    $this->registrant = Registrant::load($registrant_id);

    $field_name = Unicode::strtolower($this->randomMachineName());
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'registrant',
      'type' => 'string',
    ])->save();

    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'registrant',
      'bundle' => 'registrant',
    ])->save();

    $form_display = entity_get_form_display('registrant', 'registrant', 'default');
    $form_display->setComponent($field_name, [
      'type' => 'text_textfield',
      'weight' => 1,
    ]);
    $form_display->save();

    $this->registrant->{$field_name} = $this->randomString();
    $this->registrant->save();

    $this->registrantTestField = $field_name;
  }

  /**
   * Test access registrant form.
   */
  function testRegistrantEditRoute() {
    $admin = $this->drupalCreateUser(['administer rng']);
    $this->drupalLogin($admin);

    $this->drupalGet(Url::fromRoute('entity.registrant.edit_form', [
      'registrant' => $this->registrant->id(),
    ]));
    $this->assertResponse(200);
    $this->assertFieldByName($this->registrantTestField . '[0][value]');

    // Breadcrumb.
    $this->assertLink(t('Home'));
    $this->assertLink($this->registrant->getRegistration()->getEvent()->label());
    $this->assertLink($this->registrant->getRegistration()->label());
    $this->assertLink($this->registrant->label());
  }

  /**
   * Test access registrant form with no permission.
   */
  function testRegistrantEditRouteNoAccess() {
    $admin = $this->drupalCreateUser();
    $this->drupalLogin($admin);

    $this->drupalGet(Url::fromRoute('entity.registrant.edit_form', [
      'registrant' => $this->registrant->id(),
    ]));
    $this->assertResponse(403);
  }

}
