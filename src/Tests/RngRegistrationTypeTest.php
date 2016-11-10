<?php

namespace Drupal\rng\Tests;

use Drupal\Core\Url;
use Drupal\rng\Entity\RegistrationType;
use Drupal\rng\Entity\Registration;
use Drupal\rng\EventManagerInterface;

/**
 * Tests registration types.
 *
 * @group rng
 */
class RngRegistrationTypeTest extends RngSiteTestBase {

  public static $modules = ['block'];

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $event;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $bundle = $this->event_bundle->id();
    $account = $this->drupalCreateUser(['edit own ' . $bundle . ' content']);
    $this->drupalLogin($account);

    $this->event = $this->createEntity($this->event_bundle, [
      'uid' => \Drupal::currentUser()->id()
    ]);

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Test registration types in UI.
   */
  function testRegistrationTypes() {
    $web_user = $this->drupalCreateUser(['administer registration types', 'access administration pages']);
    $this->drupalLogin($web_user);

    // Create and delete the testing registration type.
    $this->drupalGet('admin/structure/rng/registration_types/manage/' . $this->registration_type->id());
    $this->registration_type->delete();

    // Administration.
    $this->drupalGet('admin/structure');
    $this->assertLinkByHref(Url::fromRoute('rng.registration_type.overview')->toString());

    $this->drupalGet('admin/structure/rng/registration_types');
    $this->assertRaw('No registration types found.', 'Registration type list is empty');
    $this->assertEqual(0, count(RegistrationType::loadMultiple()));

    // Local action.
    $this->assertLinkByHref(Url::fromRoute('entity.registration_type.add')->toString());

    // Add.
    $edit = ['label' => 'Foobar1', 'id' => 'foobar'];
    $this->drupalPostForm('admin/structure/rng/registration_types/add', $edit, t('Save'));
    $this->assertRaw(t('%label registration type was added.', ['%label' => 'Foobar1']));
    $this->assertEqual(1, count(RegistrationType::loadMultiple()));

    // Registration type list.
    $this->assertUrl(Url::fromRoute('rng.registration_type.overview', [], ['absolute' => TRUE])->toString(), []);
    $this->assertRaw('<td>Foobar1</td>', 'New registration type shows in list.');

    // Edit.
    $edit = ['label' => 'Foobar2'];
    $this->drupalPostForm('admin/structure/rng/registration_types/manage/foobar', $edit, t('Save'));
    $this->assertRaw(t('%label registration type was updated.', ['%label' => 'Foobar2']));

    $registration[0] = $this->createRegistration($this->event, 'foobar');
    $registration[1] = $this->createRegistration($this->event, 'foobar');

    $this->drupalGet('admin/structure/rng/registration_types/manage/foobar/delete');
    $this->assertRaw(\Drupal::translation()->formatPlural(
      count($registration),
      'Unable to delete registration type. It is used by @count registration.',
      'Unable to delete registration type. It is used by @count registrations.'
    ));

    $registration[0]->delete();
    $registration[1]->delete();

    // No registrations; delete is allowed.
    $this->drupalGet('admin/structure/rng/registration_types/manage/foobar/delete');
    $this->assertRaw(t('This action cannot be undone.'));

    // Delete.
    $this->drupalPostForm('admin/structure/rng/registration_types/manage/foobar/delete', [], t('Delete'));
    $this->assertRaw(t('Registration type %label was deleted.', ['%label' => 'Foobar2']));
    $this->assertEqual(0, count(RegistrationType::loadMultiple()), 'Registration type entity removed from storage.');
  }

  /**
   * Test registration type deletion.
   */
  function testRegistrationTypeAPIDelete() {
    // Associate event with registration type.
    $this->event->{EventManagerInterface::FIELD_REGISTRATION_TYPE}
      ->appendItem(['target_id' => $this->registration_type->id()]);
    $this->event->save();

    $this->assertEqual(1, $this->countEventRegistrationTypeReferences(
      $this->event->getEntityTypeId(), $this->registration_type->id()
    ), 'One reference exists to this registration type');

    $registration[0] = $this->createRegistration($this->event, $this->registration_type->id());
    $this->registration_type->delete();

    $this->assertIdentical(0, count(Registration::loadMultiple()), 'Registrations no longer exist');
    $this->assertEqual(0, $this->countEventRegistrationTypeReferences(
      $this->event->getEntityTypeId(), $this->registration_type->id()
    ), 'No references from event entities to this registration type');
  }

  /**
   * Count references from event entities to registration types.
   *
   * @param string $entity_type
   *   An entity type ID.
   * @param string $registration_type
   *   A registration type ID.
   *
   * @return int
   *   Number of references.
   */
  function countEventRegistrationTypeReferences($entity_type, $registration_type) {
    return \Drupal::entityTypeManager()->getStorage($entity_type)
      ->getQuery()
      ->condition(EventManagerInterface::FIELD_REGISTRATION_TYPE, $registration_type)
      ->count()
      ->execute();
  }

}
