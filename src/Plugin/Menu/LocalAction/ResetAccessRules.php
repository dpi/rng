<?php

namespace Drupal\rng\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\rng\EventManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Modifies the reset access rules action.
 */
class ResetAccessRules extends LocalActionDefault {

  use StringTranslationTrait;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * The current route matcher.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRoute;

  /**
   * Constructs a LocalActionDefault object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider to load routes by name.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route
   *   The current route matcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteProviderInterface $route_provider, EventManagerInterface $event_manager, RouteMatchInterface $current_route) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_provider);

    $this->eventManager = $event_manager;
    $this->currentRoute = $current_route;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('router.route_provider'),
      $container->get('rng.event_manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    $route = $this->routeProvider->getRouteByName($this->getRouteName());
    $param = $route->getDefault('event');

    if ($event = $this->currentRoute->getParameter($param)) {
      if ($this->eventManager->getMeta($event)->isDefaultRules('rng_event.register')) {
        return $this->t('Customize access rules');
      }
      else {
        return $this->t('Reset access rules to site default');
      }
    }
  }

}
