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
   * Event type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $eventTypeStorage;

  /**
   * Constructs a new EventManager object.
   *
   * @param EntityManager $entity_manager
   *   The entity manager.
   */
  function __construct(EntityManager $entity_manager) {
    $this->eventTypeStorage = $entity_manager->getStorage('event_type');
  }

  /**
   * {@inheritdoc}
   */
  public function getMeta(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $id = $entity->id();

    if (!$this->isEvent($entity)) {
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
  public function isEvent(EntityInterface $entity) {
    return (boolean) $this->eventType($entity->getEntityTypeId(), $entity->bundle());
  }

  /**
   * {@inheritdoc}
   */
  function eventType($entity_type, $bundle) {
    $ids = $this->eventTypeStorage->getQuery()
      ->condition('entity_type', $entity_type, '=')
      ->condition('bundle', $bundle, '=')
      ->execute();

    if ($ids) {
      return $this->eventTypeStorage
        ->load(reset($ids));
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  function eventTypeWithEntityType($entity_type) {
    $ids = $this->eventTypeStorage->getQuery()
      ->condition('entity_type', $entity_type, '=')
      ->execute();

    if ($ids) {
      return $this->eventTypeStorage->loadMultiple($ids);
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  function getEventTypes() {
    /** @var \Drupal\rng\EventTypeInterface[] $event_types */
    $entity_type_bundles = [];
    foreach ($this->eventTypeStorage->loadMultiple() as $entity) {
      $entity_type_bundles[$entity->getEventEntityTypeId()][$entity->getEventBundle()] = $entity;
    }
    return $entity_type_bundles;
  }

}
