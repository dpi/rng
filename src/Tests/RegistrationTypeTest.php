<?php

/**
 * @file
 * Contains \Drupal\rng\Tests\RegistrationTypeTest.
 */

namespace Drupal\rng\Tests;

use Drupal\Core\Url;
use Drupal\rng\Entity\RegistrationType;

/**
 * Tests registration types.
 *
 * @group rng
 */
class RegistrationTypeTest extends RNGTestBase {

  function testRegistrationTypes() {
    $web_user = $this->drupalCreateUser(['administer registration types', 'access administration pages']);
    $this->drupalLogin($web_user);

    // Create and delete the testing registration type
    $test_registration_type = $this->createRegistrationType();
    $this->drupalGet('admin/structure/rng/registration_types/manage/registration_type_a');
    $test_registration_type->delete();

    // Administration
    $this->drupalGet('admin/structure');
    $this->assertLinkByHref(Url::fromRoute('rng.registration_type.overview')->toString());

    $this->drupalGet('admin/structure/rng/registration_types');
    $this->assertRaw('There is no Registration type yet.');
    $this->assertEqual(0, count(RegistrationType::loadMultiple()));

    // Local action
    $this->assertLinkByHref(Url::fromRoute('entity.registration_type.add')->toString());

    // Add
    $edit = ['label' => 'Foobar1', 'id' => 'foobar'];
    $this->drupalPostForm('admin/structure/rng/registration_types/add', $edit, t('Save'));
    $this->assertRaw(t('%label registration type was added.', ['%label' => 'Foobar1']));
    $this->assertEqual(1, count(RegistrationType::loadMultiple()));

    // Registration type list
    $this->assertUrl(Url::fromRoute('rng.registration_type.overview', [], ['absolute' => TRUE])->toString(), []);
    $this->assertRaw('<td>Foobar1</td>', 'New registration type shows in list.');

    // Edit
    $edit = ['label' => 'Foobar2'];
    $this->drupalPostForm('admin/structure/rng/registration_types/manage/foobar', $edit, t('Save'));
    $this->assertRaw(t('%label registration type was updated.', ['%label' => 'Foobar2']));

    // No registrations; delete is allowed.
    $this->drupalGet('admin/structure/rng/registration_types/manage/foobar/delete');
    $this->assertRaw(t('This action cannot be undone.'));

    // Delete
    $this->drupalPostForm('admin/structure/rng/registration_types/manage/foobar/delete', [], t('Delete'));
    $this->assertRaw(t('Registration type %label was deleted.', ['%label' => 'Foobar2']));
    $this->assertEqual(0, count(RegistrationType::loadMultiple()));
  }

}