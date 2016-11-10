<?php

namespace Drupal\rng\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\rng\EventManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides dynamic local actions for RNG.
 */
class LocalActions extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a LocalTasks object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, RouteProviderInterface $route_provider, EventManagerInterface $event_manager) {
    $this->entityManager = $entity_manager;
    $this->routeProvider = $route_provider;
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager'),
      $container->get('router.route_provider'),
      $container->get('rng.event_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    /** @var \Drupal\rng\Entity\EventType[] $event_types */
    foreach ($this->eventManager->getEventTypes() as $entity_type => $event_types) {
      $cache_tags = $this->entityManager
        ->getDefinition($entity_type)
        ->getListCacheTags();
      foreach ($event_types as $event_type) {
        $cache_tags = Cache::mergeTags($cache_tags, $event_type->getCacheTags());
      }

      // Only need one set of actions per entity type.
      $this->derivatives["rng.event.$entity_type.event.access.reset"] = array(
        'title' => $this->t('Reset/customize access rules'),
        'route_name' => "rng.event.$entity_type.access.reset",
        'class' => '\Drupal\rng\Plugin\Menu\LocalAction\ResetAccessRules',
        'appears_on' => array("rng.event.$entity_type.access"),
        'cache_tags' => $cache_tags,
      );

      $this->derivatives["rng.event.$entity_type.event.message.add"] = array(
        'title' => $this->t('Add message'),
        'route_name' => "rng.event.$entity_type.messages.add",
        'appears_on' => array("rng.event.$entity_type.messages"),
        'cache_tags' => $cache_tags,
      );

      $this->derivatives["rng.event.$entity_type.event.group.add"] = array(
        'title' => $this->t('Add group'),
        'route_name' => "rng.event.$entity_type.group.add",
        'appears_on' => array("rng.event.$entity_type.group.list"),
        'cache_tags' => $cache_tags,
      );
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
