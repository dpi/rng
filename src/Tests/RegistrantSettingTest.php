<?php

/**
 * @file
 * Contains \Drupal\rng\Tests\RegistrantSettingTest.
 */

namespace Drupal\rng\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests registrant settings.
 *
 * @group RNG
 */
class RegistrantSettingTest extends WebTestBase {

  public static $modules = array('rng');

  public static function getInfo() {
    return array(
      'name' => 'RNG registrant settings',
      'description' => 'RNG registrant settings',
      'group' => 'RNG',
    );
  }

  function testRegistrantSettings() {
    $web_user = $this->drupalCreateUser(['administer rng', 'access administration pages']);
    $this->drupalLogin($web_user);

    $this->drupalGet('admin/config');
    $this->assertRaw('Manage registrant settings.', 'Button shows in administration.');

    $this->drupalGet('admin/config/rng/registrant');
    $this->assertRaw('Enable identity types who can register for events.', 'Registration settings form is rendered.');
    $this->assertTrue(in_array('user', $this->config('rng.settings')->get('identity_types')), 'Registrant types install config contains user registrant pre-enabled.');
    $this->assertFieldChecked('edit-contactables-user', 'User registrant checkbox is pre-checked');

    $edit = ['contactables[user]' => FALSE];
    $this->drupalPostForm('admin/config/rng/registrant', $edit, t('Save configuration'));
    $this->assertRaw('Registrant settings updated.', 'Registrant settings form saved.');
    $this->assertNoFieldChecked('edit-contactables-user', 'User registrant checkbox is now unchecked');

    $this->assertIdentical(0, count($this->config('rng.settings')->get('identity_types')), 'All registration types disabled and saved to config.');
  }

}