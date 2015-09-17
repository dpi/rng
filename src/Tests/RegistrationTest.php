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
  }

  /**
   * Test registrations.
   */
  function testRegistration() {
    $this->assertIdentical(0, count(Registration::loadMultiple()), 'There are no registrations');

    // Test registration creation.
    $registration[0] = $this->createRegistration($this->event, $this->registration_type->id());
    $this->assertIdentical(1, count(Registration::loadMultiple()), 'There is one registration');
  }

}
