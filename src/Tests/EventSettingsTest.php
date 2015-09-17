<?php

/**
 * @file
 * Contains \Drupal\rng\Tests\EventSettingsTest.
 */

namespace Drupal\rng\Tests;

/**
 * Tests event settings page.
 *
 * @group rng
 */
class EventSettingsTest extends RNGSiteTestBase {

  public static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Create two bundles of the same entity type, one bundle is an event type.
   *
   * Check if entities of each bundle are events.
   */
  function testEvent() {
    $bundle[0] = $this->event_bundle;
    $bundle[1] = $this->drupalCreateContentType();
    $bundle[2] = $this->drupalCreateContentType();
    $event_types[0] = $this->event_type;
    $event_types[1] = $this->createEventType($bundle[2]);

    $account = $this->drupalCreateUser([
      'edit own ' . $bundle[0]->id() . ' content',
      'edit own ' . $bundle[1]->id() . ' content',
    ]);
    $this->drupalLogin($account);

    $entity[0] = $this->createEntity($bundle[0]);
    $entity[1] = $this->createEntity($bundle[1]);

    $base_url = 'node/1';
    $this->drupalGet($base_url);
    $this->assertLinkByHref($base_url . '/event');
    $this->drupalGet($base_url . '/event');
    $this->assertResponse(200);

    $base_url = 'node/2';
    $this->drupalGet($base_url);
    // Need for test for both existing and non existing links,
    // errors could show, and assertNoLink could be true.
    $this->assertLinkByHref($base_url);
    $this->assertNoLinkByHref($base_url . '/event');
    $this->drupalGet($base_url . '/event');
    $this->assertResponse(403);

    // Ensure that after removing an event type, the Event links do not persist
    // for other entities of the same entity type, but different bundle.
    foreach ([403, 404] as $code) {
      $event_type = array_shift($event_types);
      $event_type->delete();
      \Drupal::service('router.builder')->rebuildIfNeeded();
      foreach (['node/1', 'node/2'] as $base_url) {
        $this->drupalGet($base_url . '/event');
        $this->assertResponse($code);
        $this->drupalGet($base_url);
        $this->assertLinkByHref($base_url);
        $this->assertNoLinkByHref($base_url . '/event');
      }
    }
  }

  /**
   * Tests canonical event page, and the Event default local task.
   */
  function testEventSettings() {
    $account = $this->drupalCreateUser([
      'edit own ' . $this->event_bundle->id() . ' content',
    ]);
    $this->drupalLogin($account);

    $event = $this->createEntity($this->event_bundle);

    // Local task appears on canonical route.
    $base_url = 'node/1';
    $this->drupalGet($event->urlInfo());
    $this->assertLinkByHref($base_url . '/event');

    // Event settings form.
    $this->drupalGet('node/1/event');
    $this->assertLink('Settings');
    $this->assertLinkByHref($base_url . '/event/access');
    $this->assertLinkByHref($base_url . '/event/messages');
    $this->assertLinkByHref($base_url . '/event/groups');
  }

}
