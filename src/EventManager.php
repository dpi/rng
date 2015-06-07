<?php

  /**
   * @file
   * Contains \Drupal\rng\EventManager.
   */

namespace Drupal\rng;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\rng\Exception\InvalidEventException;

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

    if (!$this->event_type($entity->getEntityTypeId(), $entity->bundle())) {
      throw new InvalidEventException(sprintf('%s: %s is not an event bundle.', $entity->getEntityTypeId(), $entity->bundle()));
    }

    if (!isset($this->event_meta[$entity_type][$id])) {
      $this->event_meta[$entity_type][$id] = EventMeta::createInstance($this->container, $entity);
    }

    return $this->event_meta[$entity_type][$id];
  }

  /**
   * {@inheritdoc}
   */
  function event_type($entity_type, $bundle) {
    $ids = $this->entityManager->getStorage('event_type_config')->getQuery()
      ->condition('entity_type', $entity_type, '=')
      ->condition('bundle', $bundle, '=')
      ->execute();

    if ($ids) {
      return $this->entityManager->getStorage('event_type_config')
        ->load(reset($ids));
    }

    return NULL;
  }

  function eventTypeWithEntityType($entity_type) {
    $ids = $this->entityManager->getStorage('event_type_config')->getQuery()
      ->condition('entity_type', $entity_type, '=')
      ->execute();

    if ($ids) {
      return $this->entityManager->getStorage('event_type_config')
        ->loadMultiple($ids);
    }

    return [];
  }

}
