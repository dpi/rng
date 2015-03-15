<?php

  /**
   * @file
   * Contains \Drupal\rng\EventManager.
   */

namespace Drupal\rng;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityInterface;

  /**
   * Event manager for RNG.
   */
class EventManager implements EventManagerInterface {

  use ContainerAwareTrait;

  /**
   * An array of event meta instances.
   *
   * @var \Drupal\rng\EventMeta[]
   */
  protected $event_meta = [];

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new EventManager object.
   *
   * @param EntityManager $entity_manager
   *   The entity manager.
   */
  function __construct(EntityManager $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getMeta(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $id = $entity->id();

    if (!isset($this->event_meta[$entity_type][$id])) {
      $this->event_meta[$entity_type][$id] = EventMeta::createInstance($this->container, $entity);
    }

    return $this->event_meta[$entity_type][$id];
  }

  /**
   * {@inheritdoc}
   */
  function event_type($entity_type, $bundle) {
    return $this->entityManager->getStorage('event_type_config')->load("$entity_type.$bundle");
  }

}