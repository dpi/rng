<?php

namespace Drupal\Tests\rng\Functional;

use Drupal\Core\Url;
use Drupal\rng\Form\EventTypeForm;

/**
 * Tests manage event access page.
 *
 * @group rng
 */
class RngEventAccessWebTest extends RngBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'block'];

  /**
   * A registration type for testing.
   *
   * @var \Drupal\rng\RegistrationTypeInterface
   */
  protected $registrationType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->registrationType = $this->createRegistrationType();
    $this->createEventType('entity_test', 'entity_test');
    EventTypeForm::createDefaultRules('entity_test', 'entity_test');

    $this->container->get('router.builder')->rebuildIfNeeded();
    $this->container->get('plugin.manager.menu.local_action')->clearCachedDefinitions();

    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Test event access page when using site defaults.
   */
  public function testEventAccessSiteDefaults() {
    $user1 = $this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
    ]);
    $this->drupalLogin($user1);

    $event_meta = $this->createEvent(['user_id' => $user1->id()]);

    $this->drupalGet(Url::fromRoute('rng.event.entity_test.access', [
      'entity_test' => $event_meta->getEvent()->id(),
    ]));

    // Reset access rules button.
    $reset_link = Url::fromRoute('rng.event.entity_test.access.reset', [
      'entity_test' => $event_meta->getEvent()->id(),
    ]);
    $this->assertSession()->linkExists(t('Customize access rules'));
    $this->assertSession()->linkByHrefExists($reset_link->toString());

    // Check if one of the checkboxes is disabled.
    $field_name = 'table[6][operation_create][enabled]';
    $this->assertSession()->fieldExists($field_name);
    $input = $this->xpath('//input[@name="' . $field_name . '" and @disabled="disabled"]');
    $this->assertTrue(count($input) === 1, 'The create checkbox is disabled.');
  }

  /**
   * Test event access page when using custom rules.
   */
  public function testEventAccessCustomized() {
    $user1 = $this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
    ]);
    $this->drupalLogin($user1);

    $event_meta = $this->createEvent(['user_id' => $user1->id()]);
    $event_meta->addDefaultAccess();

    $this->drupalGet(Url::fromRoute('rng.event.entity_test.access', [
      'entity_test' => $event_meta->getEvent()->id(),
    ]));

    // Reset access rules button.
    $reset_link = Url::fromRoute('rng.event.entity_test.access.reset', [
      'entity_test' => $event_meta->getEvent()->id(),
    ]);
    $this->assertSession()->linkExists(t('Reset access rules to site default'));
    $this->assertSession()->linkByHrefExists($reset_link->toString());

    // Check if one of the checkboxes is enabled.
    $field_name = 'table[6][operation_create][enabled]';
    $this->assertSession()->fieldExists($field_name);
    $input = $this->xpath('//input[@name="' . $field_name . '" and @disabled="disabled"]');
    $this->assertTrue(count($input) === 0, 'The create checkbox is not disabled.');
  }

}
