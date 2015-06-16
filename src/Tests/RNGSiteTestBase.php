<?php

/**
 * @file
 * Definition of Drupal\rng\Tests\RNGSiteTestBase.
 */

namespace Drupal\rng\Tests;

/**
 * Sets up page and article content types.
 */
abstract class RNGSiteTestBase extends RNGTestBase {

  public static $modules = array('rng', 'node');

  /**
   * @var \Drupal\rng\RegistrationTypeInterface
   */
  var $registration_type;

  /**
   * @var \Drupal\rng\EventTypeInterface
   */
  var $event_bundle;

  /**
   * @var \Drupal\rng\EventTypeInterface
   */
  var $event_type;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->event_bundle = $this->drupalCreateContentType();
    $this->event_type = $this->createEventType($this->event_bundle);
    $this->registration_type = $this->createRegistrationType();
  }

}
