<?php

namespace Drupal\rng\Tests;

use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\rng\EventManagerInterface;
use Drupal\rng\Entity\Rule;
use Drupal\rng\Entity\RuleComponent;
use Drupal\rng\Entity\EventTypeRule;

/**
 * Tests event type access defaults.
 *
 * @group rng
 */
class RngEventTypeAccessDefaultsTest extends RngWebTestBase {

  public static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin = $this->drupalCreateUser(['administer event types']);
    $this->drupalLogin($admin);
  }

  /**
   * Test access defaults.
   */
  function testAccessDefaults() {
    $edit = [
      'bundle' => 'entity_test.entity_test',
      'registrants[registrant_type]' => 'registrant',
    ];
    $this->drupalPostForm(Url::fromRoute('entity.event_type.add'), $edit, t('Save'));

    $defaults_route = Url::fromRoute('entity.event_type.access_defaults', [
      'event_type' => 'entity_test.entity_test',
    ]);
    $this->drupalGet($defaults_route);

    // Ensure checkboxes have default values.
    $this->assertNoFieldById('edit-actions-operations-event-manager-create');
    $this->assertFieldChecked('edit-actions-operations-event-manager-view');
    $this->assertFieldChecked('edit-actions-operations-event-manager-update');
    $this->assertFieldChecked('edit-actions-operations-event-manager-delete');

    $this->assertNoFieldById('edit-actions-operations-registrant-create');
    $this->assertFieldChecked('edit-actions-operations-registrant-view');
    $this->assertFieldChecked('edit-actions-operations-registrant-update');
    $this->assertNoFieldChecked('edit-actions-operations-registrant-delete');


    $this->assertFieldChecked('edit-actions-operations-user-role-create');
    $this->assertNoFieldChecked('edit-actions-operations-user-role-view');
    $this->assertNoFieldChecked('edit-actions-operations-user-role-update');
    $this->assertNoFieldChecked('edit-actions-operations-user-role-delete');

    $edit = [
      'actions[operations][user_role][delete]' => TRUE,
    ];
    $this->drupalPostForm($defaults_route, $edit, t('Save'));

    $this->assertRaw(t('Event type access defaults saved.'));
    // Update field still unchecked.
    $this->assertNoFieldChecked('edit-actions-operations-user-role-update');
    // Delete field is now checked.
    $this->assertFieldChecked('edit-actions-operations-user-role-delete');
  }

}
