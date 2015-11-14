<?php

/**
 * @file
 * Contains \Drupal\rng\Tests\RegistrationTest.
 */

namespace Drupal\rng\Tests;

use Drupal\rng\Entity\Registration;

/**
 * Tests registration entities.
 *
 * @group rng
 */
class RegistrationTest extends RNGSiteTestBase {

  public static $modules = ['block'];

  /** @var \Drupal\Core\Entity\EntityInterface $event */
  var $event;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $bundle = $this->event_bundle->id();
    $account = $this->drupalCreateUser([
      'access content',
      'edit own ' . $bundle . ' content',
      'rng register self',
    ]);
    $this->drupalLogin($account);

    $this->event = $this->createEntity($this->event_bundle, [
      'uid' => \Drupal::currentUser()->id()
    ]);

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Test registrations.
   */
  function testRegistrationAPI() {
    $this->assertIdentical(0, count(Registration::loadMultiple()), 'There are no registrations');

    // Test registration creation.
    $registration[0] = $this->createRegistration($this->event, $this->registration_type->id());
    $this->assertIdentical(1, count(Registration::loadMultiple()), 'There is one registration');
  }

  /**
   * Create new registration via UI.
   *
   * Enable registrations for an event and submit new registration form.
   */
  function testRegistration() {
    // Event
    $base_url = 'node/1';
    $this->drupalGet($base_url . '');
    $this->assertResponse(200);
    $this->drupalGet($base_url . '/event');
    $this->assertResponse(200);
    $this->assertNoLinkByHref($base_url . '/register');
    $this->drupalGet($base_url . '/register');
    $this->assertResponse(403);

    // Settings
    $edit = [
      'rng_status[value]' => TRUE,
      'rng_registration_type[' . $this->registration_type->id() . ']' => TRUE,
      'rng_capacity[0][unlimited_number][unlimited_number]' => 'limited',
      'rng_capacity[0][unlimited_number][number]' => '1',
    ];
    $this->drupalPostForm($base_url . '/event', $edit, t('Save'));
    $this->assertRaw(t('Event settings updated.'));

    // Register tab appears.
    $this->assertLinkByHref($base_url . '/register');

    // Registration form.
    $this->drupalGet($base_url . '/register');
    $this->assertResponse(200);
    $this->assertRaw(t('My account: %username', ['%username' => \Drupal::currentUser()->getAccountName()]));

    $edit = [
      'identity' => 'user:' . \Drupal::currentUser()->id(),
    ];
    $this->drupalPostForm($base_url . '/register', $edit, t('Save'));
    $this->assertRaw(t('Registration has been created.'));
  }

}
