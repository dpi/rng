<?php

/**
 * @file
 * Contains \Drupal\rng\Tests\EventTypeTest.
 */

namespace Drupal\rng\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\rng\Entity\EventTypeConfig;
use Drupal\node\Entity\NodeType;

/**
 * Tests EventTypeConfig
 *
 * @group RNG
 */
class EventTypeTest extends WebTestBase {

  public static $modules = array('rng', 'node');

  public static function getInfo() {
    return array(
      'name' => 'RNG EventTypeConfigTest',
      'description' => 'Test EventTypeConfig.',
      'group' => 'RNG',
    );
  }

  function testEventType() {
    $web_user = $this->drupalCreateUser(['administer event types', 'access administration pages']);
    $this->drupalLogin($web_user);

    // Event types button on admin
    $this->drupalGet('admin/config');
    $this->assertRaw('Manage which entity bundles are designated as events.', 'Event type button shows in administration.');

    // No events
    $event_types = EventTypeConfig::loadMultiple();
    $this->assertEqual(0, count($event_types), 'There are no event type entities.');
    $this->drupalGet('admin/config/rng/event_types');
    $this->assertRaw('There is no Event configuration type yet.', 'Event Type list is empty');

    // Add
    $t_args = ['%label' => 'node.event'];
    $edit = [];
    $this->drupalPostForm('admin/config/rng/event_types/add', [], t('Save'));
    $node_type = NodeType::load('event');

    $event_types = EventTypeConfig::loadMultiple();
    $this->assertEqual(1, count($event_types), 'Event type exists in database.');
    $this->assertRaw(t('The content type !link has been added.', ['!link' => $node_type->link()]), 'Node was created for Event Type');
    $this->assertRaw(t('%label event type added.', $t_args), 'Event Type created');

    // Event type list
    $this->assertUrl('admin/config/rng/event_types', [], 'Browser was redirected to event type list.');
    $this->assertRaw('<td>node.event</td>', 'Event Type shows in list');

    // Edit form
    $this->drupalGet('admin/config/rng/event_types/manage/node.event/edit');
    $this->assertRaw(t('Edit event type %label configuration', $t_args), 'Event Type edit form rendered.');

    $edit = [];
    $this->drupalPostForm('admin/config/rng/event_types/manage/node.event/edit', [], t('Save'));
    $this->assertRaw(t('%label event type updated.', $t_args), 'Event Type edit form saved');

    // Delete form
    $this->drupalGet('admin/config/rng/event_types/manage/node.event/delete');
    $this->assertRaw('Are you sure you want to delete settings for event node.event and all associated registrations?', 'Event Type delete form rendered.');

    $this->drupalPostForm('admin/config/rng/event_types/manage/node.event/delete', [], t('Delete'));
    $this->assertRaw(t('Event type %label was deleted.', $t_args), 'Event Type delete form saved');

    $event_types = EventTypeConfig::loadMultiple();
    $this->assertEqual(0, count($event_types), 'Event type deleted from database.');

    // @todo: ensure conditional on form omits node/existing radios
    // @todo create event type with custom entity
  }

}