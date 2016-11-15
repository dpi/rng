<?php

namespace Drupal\rng\Tests;

/**
 * Tests event settings page.
 *
 * @group rng
 */
class RngEventSettingsTest extends RngSiteTestBase {

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
  public function testEvent() {
    $bundle[0] = $this->event_bundle;
    $bundle[1] = $this->drupalCreateContentType();
    $bundle[2] = $this->drupalCreateContentType();
    $event_types[0] = $this->event_type;
    $event_types[1] = $this->createEventType('node', $bundle[2]->id());

    \Drupal::service('router.builder')->rebuildIfNeeded();

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
  public function testEventSettingsTabs() {
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

  /**
   * Tests changing event settings reveals the 'Register' tab.
   */
  public function testEventSettings() {
    $bundle = $this->event_bundle->id();
    $account = $this->drupalCreateUser([
      'access content',
      'edit own ' . $bundle . ' content',
      'rng register self',
    ]);
    $this->drupalLogin($account);

    $this->createEntity($this->event_bundle, [
      'uid' => \Drupal::currentUser()->id()
    ]);

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
  }

}
