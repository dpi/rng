<?php

namespace Drupal\Tests\rng\Kernel\Views;

use Drupal\Core\Link;
use Drupal\rng\Tests\RngTestTrait;
use Drupal\simpletest\UserCreationTrait;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;

/**
 * Tests RNG field plugins.
 *
 * @group rng
 */
class RngViewsTest extends ViewsKernelTestBase {

  use RngTestTrait;
  use UserCreationTrait {
    createUser as drupalCreateUser;
  }

  /**
   * {@inheritdoc}
   *
   */
  public static $modules = ['user', 'courier', 'unlimited_number', 'rng_test_views', 'rng', 'entity_test', 'field', 'dynamic_entity_reference', 'text'];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_rng'];

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * A registration type for testing.
   *
   * @var \Drupal\rng\RegistrationTypeInterface
   */
  protected $registrationType;

  /**
   * A view for testing.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * @inheritdoc
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->eventManager = $this->container->get('rng.event_manager');

    $this->installConfig(['field']);
    $this->installConfig(['rng']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('registration');
    $this->installEntitySchema('registrant');
    $this->installEntitySchema('rng_rule');
    $this->installEntitySchema('rng_rule_component');

    ViewTestData::createTestViews(get_class($this), ['rng_test_views']);

    $this->registrationType = $this->createRegistrationType();
    $this->createEventType('entity_test', 'entity_test');

    $this->view = Views::getView('test_rng');
    $this->view->setDisplay();
  }

  /**
   * Test register link field.
   */
  public function testRegisterLink() {
    $event = $this->createEvent()->getEvent();
    $user1 = $this->drupalCreateUser(['rng register self']);
    $this->setCurrentUser($user1);
    $this->createUserRoleRules([], ['create' => TRUE]);

    $this->view->displayHandlers->get('default')->overrideOption('fields', [
      'rng_event_register' => [
        'table' => 'entity_test',
        'field' => 'rng_event_register',
        'id' => 'rng_event_register',
        'plugin_id' => 'rng_event_register',
        'entity_type' => 'entity_test',
      ],
    ]);
    $this->view->save();

    $this->view->preview();
    $expected = Link::createFromRoute(t('Register'), 'rng.event.entity_test.register.type_list', [
      'entity_test' => $event->id(),
    ])->toString();
    $actual = $this->view->style_plugin->getField(0, 'rng_event_register');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Test register link field with a registration type.
   */
  public function testRegisterLinkWithRegistrationType() {
    $event = $this->createEvent()->getEvent();
    $user1 = $this->drupalCreateUser(['rng register self']);
    $this->setCurrentUser($user1);
    $this->createUserRoleRules([], ['create' => TRUE]);

    $this->view->displayHandlers->get('default')->overrideOption('fields', [
      'rng_event_register' => [
        'table' => 'entity_test',
        'field' => 'rng_event_register',
        'id' => 'rng_event_register',
        'plugin_id' => 'rng_event_register',
        'entity_type' => 'entity_test',
        'registration_type' => $this->registrationType->id(),
      ],
    ]);
    $this->view->save();

    $this->view->preview();
    $expected = Link::createFromRoute(t('Register'), 'rng.event.entity_test.register', [
      'entity_test' => $event->id(),
      'registration_type' => $this->registrationType->id(),
    ])->toString();
    $actual = $this->view->style_plugin->getField(0, 'rng_event_register');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Test register link field with no access produces empty field.
   */
  public function testRegisterLinkNoAccess() {
    $this->createEvent();
    $user1 = $this->drupalCreateUser(['rng register self']);
    $this->setCurrentUser($user1);
    $this->createUserRoleRules([], []);

    $this->view->displayHandlers->get('default')->overrideOption('fields', [
      'rng_event_register' => [
        'table' => 'entity_test',
        'field' => 'rng_event_register',
        'id' => 'rng_event_register',
        'plugin_id' => 'rng_event_register',
        'entity_type' => 'entity_test',
      ],
    ]);
    $this->view->save();

    $this->view->preview();
    $expected = '';
    $actual = $this->view->style_plugin->getField(0, 'rng_event_register');
    $this->assertEquals($expected, $actual);
  }

}
