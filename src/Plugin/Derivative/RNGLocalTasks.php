<?php

/**
 * @file
 * Contains \Drupal\rng\Plugin\Derivative\RNGLocalTasks.
 */

namespace Drupal\rng\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;

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
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (entity_load_multiple('event_type_config') as $event_type_config) {
      // Only need one task per entity type.
      $id = 'rng.event.' . $event_type_config->entity_type .'register';
      if (isset($this->derivatives[$id])) {
        continue;
      }

      $entity_type = $this->entityManager->getDefinition($event_type_config->entity_type);

      $this->derivatives[$id]['route_name'] = 'rng.event.' . $event_type_config->entity_type . '.register';
      $this->derivatives[$id]['base_route'] = $entity_type->getLinkTemplate('canonical');
      $this->derivatives[$id]['title'] = t('New Registration');

    }
    return $this->derivatives;
  }
}