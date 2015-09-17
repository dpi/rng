<?php

/**
 * @file
 * Contains \Drupal\rng\Tests\RegistrantSettingTest.
 */

namespace Drupal\rng\Tests;

use Drupal\Core\Url;

/**
 * Tests registrant settings.
 *
 * @group rng
 */
class RegistrantSettingTest extends RNGTestBase {

  /**
   * Test registrant settings.
   */
  function testRegistrantSettings() {
    $web_user = $this->drupalCreateUser(['administer rng', 'access administration pages']);
    $this->drupalLogin($web_user);

    $this->drupalGet('admin/config');
    $this->assertLinkByHref(Url::fromRoute('rng.config.registrant')->toString());

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
