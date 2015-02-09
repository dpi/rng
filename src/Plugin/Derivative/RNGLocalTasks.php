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

/**
 * Provides dynamic tasks for RNG.
 */
class RNGLocalTasks extends DeriverBase implements ContainerDeriverInterface {
  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a RNGLocalTasks object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(RouteProviderInterface $route_provider) {
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (entity_load_multiple('event_type_config') as $event_type_config) {
      // Only need one task per entity type.
      $id_register = 'rng.event.' . $event_type_config->entity_type .'register.type_list';
      if (isset($this->derivatives[$id_register])) {
        continue;
      }

      if ($this->routeProvider->getRouteByName('entity.' . $event_type_config->entity_type . '.canonical')) {
        $id_event = 'rng.event.' . $event_type_config->entity_type .'event';
        $this->derivatives[$id_event]['route_name'] = 'rng.event.' . $event_type_config->entity_type . '.event';
        $this->derivatives[$id_event]['base_route'] = 'entity.' . $event_type_config->entity_type . '.canonical';
        $this->derivatives[$id_event]['title'] = t('Manage Event');
        $this->derivatives[$id_event]['weight'] = t(30);

        $this->derivatives[$id_register]['route_name'] = 'rng.event.' . $event_type_config->entity_type . '.register.type_list';
        $this->derivatives[$id_register]['base_route'] = 'entity.' . $event_type_config->entity_type . '.canonical';
        $this->derivatives[$id_register]['title'] = t('New Registration');
        $this->derivatives[$id_register]['weight'] = t(40);
      }
    }
    return $this->derivatives;
  }
}