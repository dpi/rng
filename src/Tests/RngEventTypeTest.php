<?php

namespace Drupal\rng\Tests;

use Drupal\rng\Entity\EventType;
use Drupal\courier\Entity\CourierContext;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Url;

/**
 * Tests event types.
 *
 * @group rng
 */
class RngEventTypeTest extends RngWebTestBase {

  public static $modules = ['node', 'field_ui', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Test event types in UI.
   */
  function testEventType() {
    $web_user = $this->drupalCreateUser(['administer event types', 'access administration pages']);
    $this->drupalLogin($web_user);

    // Create and delete the testing event type.
    $event_bundle = $this->drupalCreateContentType();
    $event_type = $this->createEventType('node', $event_bundle->id());
    $this->drupalGet('admin/structure/rng/event_types/manage/' . $event_type->id() . '/edit');
    $event_type->delete();
    $event_bundle->delete();

    // Event types button on admin.
    $this->drupalGet('admin/structure');
    $this->assertLinkByHref(Url::fromRoute('rng.event_type.overview')->toString());
    $this->assertRaw('Manage which entity bundles are designated as events.', 'Button shows in administration.');

    // No events.
    $this->assertEqual(0, count(EventType::loadMultiple()), 'There are no event type entities.');
    $this->drupalGet('admin/structure/rng/event_types');
    $this->assertRaw('No event types found.', 'Event Type list is empty');

    // There are no courier contexts.
    $this->assertEqual(0, count(CourierContext::loadMultiple()), 'There are no courier context entities.');

    // Local action.
    $this->assertLinkByHref(Url::fromRoute('entity.event_type.add')->toString());

    // Add.
    $t_args = ['%label' => 'node.event'];
    $edit = [
      'registrants[registrant_type]' => 'registrant',
    ];
    $this->drupalPostForm('admin/structure/rng/event_types/add', $edit, t('Save'));

    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::load('event');

    $this->assertEqual(1, count(EventType::loadMultiple()), 'Event type exists in database.');

    $this->assertRaw(t('The content type <a href=":url">%label</a> has been added.', [
      '%label' => $node_type->label(),
      ':url' => $node_type->toUrl()->toString(),
    ]), 'Node was created for Event Type');
    $this->assertRaw(t('%label event type added.', $t_args), 'Event Type created');

    // Courier context created?
    $this->assertTrue(CourierContext::load('rng_registration_node'), 'Courier context entity created for this event type\' entity type.');

    // Event type list.
    $this->drupalGet('admin/structure/rng/event_types');
    $this->assertRaw('<td>Content: event</td>', 'Event Type shows in list');
    $options = ['node_type' => 'event'];
    $this->assertLinkByHref(Url::fromRoute("entity.node.field_ui_fields", $options)->toString());

    // Edit form.
    $edit = [];
    $this->drupalPostForm('admin/structure/rng/event_types/manage/node.event/edit', $edit, t('Save'));
    $this->assertRaw(t('%label event type updated.', $t_args), 'Event Type edit form saved');

    // Delete form.
    $this->drupalGet('admin/structure/rng/event_types/manage/node.event/delete');
    $this->assertRaw('Are you sure you want to delete event type node.event?', 'Event Type delete form rendered.');

    $this->drupalPostForm('admin/structure/rng/event_types/manage/node.event/delete', [], t('Delete'));
    $this->assertRaw(t('Event type %label was deleted.', $t_args), 'Event Type delete form saved');

    $this->assertEqual(0, count(EventType::loadMultiple()), 'Event type deleted from database.');

    // @todo: ensure conditional on form omits node/existing radios
    // @todo create event type with custom entity
  }

}
