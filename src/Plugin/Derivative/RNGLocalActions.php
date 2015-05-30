<?php

/**
 * @file
 * @fie
 * Contains \Drupal\rng\Plugin\Derivative\RNGLocalActions.
 */

namespace Drupal\rng\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local action for RNG.
 */
class RNGLocalActions extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The storage manager for event_type_config entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $eventTypeConfigStorage;

  /**
   * Constructs a FieldUiLocalAction object.
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

    $entity_type_config = [];
    foreach ($this->eventTypeConfigStorage->loadMultiple() as $entity) {
      $entity_type_config[$entity->entity_type][$entity->bundle] = $entity;
    }

    foreach ($entity_type_config as $entity_type => $bundles) {
      // Only need one set of actions per entity type.
      $this->derivatives["rng.event.$entity_type.event.access.reset"] = array(
        'title' => $this->t('Reset access to default'),
        'route_name' => "rng.event.$entity_type.access.reset",
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
