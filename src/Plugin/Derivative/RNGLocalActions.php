<?php

/**
 * @file
 * @fie
 * Contains \Drupal\rng\Plugin\Derivative\RNGLocalActions.
 */

namespace Drupal\rng\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\rng\EventManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local action for RNG.
 */
class RNGLocalActions extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a RNGLocalTasks object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(RouteProviderInterface $route_provider, EventManagerInterface $event_manager) {
    $this->routeProvider = $route_provider;
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('rng.event_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    $event_types = $this->eventManager->getEventTypes();
    foreach (array_keys($event_types) as $entity_type) {
      // Only need one set of actions per entity type.
      $this->derivatives["rng.event.$entity_type.event.access.reset"] = array(
        'title' => $this->t('Reset/customize access rules'),
        'route_name' => "rng.event.$entity_type.access.reset",
        'class' => '\Drupal\rng\Plugin\Menu\LocalAction\ResetAccessRules',
        'appears_on' => array("rng.event.$entity_type.access"),
      );

      $this->derivatives["rng.event.$entity_type.event.message.add"] = array(
        'title' => $this->t('Add message'),
        'route_name' => "rng.event.$entity_type.messages.add",
        'appears_on' => array("rng.event.$entity_type.messages"),
      );

      $this->derivatives["rng.event.$entity_type.event.group.add"] = array(
        'title' => $this->t('Add group'),
        'route_name' => "rng.event.$entity_type.group.add",
        'appears_on' => array("rng.event.$entity_type.group.list"),
      );
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
