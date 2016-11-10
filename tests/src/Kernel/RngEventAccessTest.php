<?php

namespace Drupal\Tests\rng\Kernel;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\simpletest\UserCreationTrait;

/**
 * Tests manage event access control. to register for events..
 *
 * @group rng
 */
class RngEventAccessTest extends RngKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'entity_test'];

  use UserCreationTrait {
    createUser as drupalCreateUser;
  }

  /**
   * Route access manager.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Route access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * An event type for testing.
   *
   * @var \Drupal\rng\EventTypeInterface
   */
  protected $eventType;

  /**
   * A list of routes only event managers have access.
   *
   * Except for the entity edit form.
   *
   * @var array
   */
  protected $routes = [
    'entity.entity_test.edit_form',
    'rng.event.entity_test.event',
    'rng.event.entity_test.access',
    'rng.event.entity_test.messages',
    'rng.event.entity_test.group.list',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->routeProvider = $this->container->get('router.route_provider');
    $this->accessManager = $this->container->get('access_manager');
    $this->eventManager = $this->container->get('rng.event_manager');

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('rng_rule');
    $this->installEntitySchema('rng_rule_component');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);

    $this->registrationType = $this->createRegistrationType();
    $this->eventType = $this->createEventType('entity_test', 'entity_test');

    // Make sure no users get uid=1.
    $root = $this->drupalCreateUser();
  }

  /**
   * Test user role with access to administer all entities.
   */
  public function testAllAccess() {
    $event = EntityTest::create();
    $event->save();
    $parameters['entity_test'] = $event;

    // 'administer entity_test content' grants update to entitytest entities.
    $account = $this->drupalCreateUser(['administer entity_test content']);

    foreach ($this->routes as $route_name) {
      $route = $this->routeProvider->getRouteByName($route_name);
      $route_match = new RouteMatch($route_name, $route, $parameters);
      $this->assertTrue($this->accessManager->check($route_match, $account), 'All access to: ' . $route_name);
    }
  }

  /**
   * Test user role with access to administer all entities but with no
   * operation mirror.
   */
  public function testAllAccessNoOperationMirror() {
    $event = EntityTest::create();
    $event->save();
    $parameters['entity_test'] = $event;

    // 'administer entity_test content' grants update to entitytest entities.
    $account = $this->drupalCreateUser(['administer entity_test content']);

    $this->eventType
      ->setEventManageOperation(NULL)
      ->save();

    foreach ($this->routes as $route_name) {
      $route = $this->routeProvider->getRouteByName($route_name);
      $route_match = new RouteMatch($route_name, $route, $parameters);

      // Access is not granted to any route except for the entity edit form.
      $access = strpos($route_name, 'edit_form') !== FALSE;
      $this->assertEquals($access, $this->accessManager->check($route_match, $account), sprintf('All access to with no operation mirror: %s (%s)', $route_name, (string) $access));
    }
  }

  /**
   * Test authenticated user without permissions.
   */
  public function testAuthenticated() {
    $event = EntityTest::create();
    $event->save();
    $parameters['entity_test'] = $event;

    $account = $this->drupalCreateUser();

    foreach ($this->routes as $route_name) {
      $route = $this->routeProvider->getRouteByName($route_name);
      $route_match = new RouteMatch($route_name, $route, $parameters);
      $this->assertFalse($this->accessManager->check($route_match, $account), 'No authenticated access to: ' . $route_name);
    }
  }

  /**
   * Test anonymous user.
   */
  public function testAnonymous() {
    $event = EntityTest::create();
    $event->save();
    $parameters['entity_test'] = $event;

    $account = new AnonymousUserSession();

    foreach ($this->routes as $route_name) {
      $route = $this->routeProvider->getRouteByName($route_name);
      $route_match = new RouteMatch($route_name, $route, $parameters);
      $this->assertFalse($this->accessManager->check($route_match, $account), 'No anonymous access to: ' . $route_name);
    }
  }

}
