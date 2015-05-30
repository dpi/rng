<?php

/**
 * @file
 * Contains \Drupal\rng\Plugin\Derivative\RNGLocalTasks.
 */

namespace Drupal\rng\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Provides dynamic tasks for RNG.
 */
class RNGLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The storage manager for event_type_config entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $eventTypeConfigStorage;

  /**
   * Constructs a RNGLocalTasks object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider to load routes by name.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(RouteProviderInterface $route_provider, EntityManagerInterface $entity_manager) {
    $this->routeProvider = $route_provider;
    $this->eventTypeConfigStorage = $entity_manager->getStorage('event_type_config');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = array();

    $entity_type_config = array();
    foreach ($this->eventTypeConfigStorage->loadMultiple() as $entity) {
      $entity_type_config[$entity->entity_type][$entity->bundle] = $entity;
    }

    foreach ($entity_type_config as $entity_type => $bundles) {
      // Only need one set of tasks task per entity type.
      if ($this->routeProvider->getRouteByName("entity.$entity_type.canonical")) {
        $event_default = "rng.event.$entity_type.event.default";
        $this->derivatives[$event_default] = array(
          'title' => t('Event'),
          'base_route' => "entity.$entity_type.canonical",
          'route_name' => "rng.event.$entity_type.event",
          'weight' => 30,
        );

        $this->derivatives["rng.event.$entity_type.event.settings"] = array(
          'title' => t('Settings'),
          'route_name' => $this->derivatives[$event_default]['route_name'],
          'parent_id' => 'rng.local_tasks:' . $event_default,
          'weight' => 10,
        );

        $this->derivatives["rng.event.$entity_type.event.access"] = array(
          'title' => t('Access'),
          'route_name' => "rng.event.$entity_type.access",
          'parent_id' => 'rng.local_tasks:' . $event_default,
          'weight' => 20,
        );

        $this->derivatives["rng.event.$entity_type.event.messages"] = array(
          'title' => t('Messages'),
          'route_name' => "rng.event.$entity_type.messages",
          'parent_id' => 'rng.local_tasks:' . $event_default,
          'weight' => 30,
        );

        $this->derivatives["rng.event.$entity_type.event.group.list"] = array(
          'title' => t('Groups'),
          'route_name' => "rng.event.$entity_type.group.list",
          'parent_id' => 'rng.local_tasks:' . $event_default,
          'weight' => 40,
        );

        $this->derivatives["rng.event.$entity_type.registrations"] = array(
          'title' => t('Registration List'),
          'route_name' => "rng.event.$entity_type.registrations",
          'base_route' => "entity.$entity_type.canonical",
          'weight' => 35,
        );

        $this->derivatives["rng.event.$entity_type.register.type_list"] = array(
          'route_name' => "rng.event.$entity_type.register.type_list",
          'base_route' => "entity.$entity_type.canonical",
          'title' => t('Register'),
          'weight' => 40,
        );
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
