<?php

namespace Drupal\rng\Tests;

use Drupal\Core\Url;

/**
 * Tests RNG settings form.
 *
 * @group rng
 */
class RngSettingsFormTest extends RngWebTestBase {

  /**
   * Test settings form menu link.
   */
  function testSettingsMenuLink() {
    $web_user = $this->drupalCreateUser(['administer rng', 'access administration pages']);
    $this->drupalLogin($web_user);

    $this->drupalGet('admin/config');
    $this->assertLinkByHref(Url::fromRoute('rng.config.settings')->toString());
  }

  /**
   * Test settings form.
   */
  function testSettingsForm() {
    $web_user = $this->drupalCreateUser(['administer rng']);
    $this->drupalLogin($web_user);

    $this->drupalGet(Url::fromRoute('rng.config.settings'));
    $this->assertRaw('Enable people types who can register for events.', 'Registration settings form is rendered.');
    $this->assertTrue(in_array('user', $this->config('rng.settings')->get('identity_types')), 'Registrant types install config contains user registrant pre-enabled.');
    $this->assertFieldChecked('edit-contactables-user', 'User registrant checkbox is pre-checked');

    $edit = ['contactables[user]' => FALSE];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertRaw('RNG settings updated.', 'Settings form saved.');
    $this->assertNoFieldChecked('edit-contactables-user', 'User registrant checkbox is now unchecked');

    $this->assertIdentical(0, count($this->config('rng.settings')->get('identity_types')), 'All identity types disabled and saved to config.');
  }

}
