<?php

/**
 * @file
 * Contains \Drupal\rng_debug\Routing\RouteSubscriber.
 */

namespace Drupal\rng_debug\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dynamic routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $entity_type_config = array();
    foreach (entity_load_multiple('event_type_config') as $entity) {
      $entity_type_config[$entity->entity_type] = $entity->entity_type;
    }

    foreach ($entity_type_config as $entity_type) {
      $definition = $this->entityManager->getDefinition($entity_type);
      if ($canonical_path = $definition->getLinkTemplate('canonical')) {
        $manage_requirements = [
          '_entity_access' => $entity_type . '.manage event',
          '_entity_is_event' => 'TRUE',
          '_permission' => 'debug rng',
        ];
        $options = [];
        $options['parameters'][$entity_type]['type'] = 'entity:' . $entity_type;

        // Rules.
        $route = new Route(
          $canonical_path . '/event/rules',
          array(
            '_controller' => '\Drupal\rng_debug\Controller\DebugController::listing',
            '_title' => 'Rules',
            'event' => $entity_type,
          ),
          $manage_requirements,
          $options
        );
        $collection->add("rng.event.$entity_type.rules", $route);
      }
    }
  }

}
