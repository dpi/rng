<?php

/**
 * @file
 * Contains \Drupal\rng\Tests\EventSettingsTest.
 */

namespace Drupal\rng\Tests;

use Drupal\Core\Url;

/**
 * Tests event settings.
 *
 * @group RNG
 */
class EventSettingsTest extends RNGSitePreConfigured {

  public static function getInfo() {
    return array(
      'name' => 'Event settings',
      'description' => 'Event settings',
      'group' => 'RNG',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $bundle = $this->event_bundle->id();
    $account = $this->drupalCreateUser(['edit own ' . $bundle . ' content']);
    $this->drupalLogin($account);

    $this->event = $this->createEvent($this->event_bundle, [
      'uid' => \Drupal::currentUser()->id()
    ]);
  }

  function testEventSettings() {
    // local task appears on canonical route
    $this->drupalGet('node/' . $this->event->id());
    $this->assertLinkByHref('node/' . $this->event->id() . '/event');
    $this->assertLinkByHref('node/' . $this->event->id() . '/registrations');

    // event settings form
    $this->drupalGet('node/' . $this->event->id() . '/event');
    $this->assertLink('Settings');
    $this->assertLinkByHref('node/' . $this->event->id() . '/event/access');
    $this->assertLinkByHref('node/' . $this->event->id() . '/event/messages');
    $this->assertLinkByHref('node/' . $this->event->id() . '/event/groups');
  }

}