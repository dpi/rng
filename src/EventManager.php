<?php

namespace Drupal\rng;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\rng\Exception\InvalidEventException;
use Drupal\Core\Cache\Cache;

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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  function __construct(EntityManagerInterface $entity_manager) {
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

  /**
   * {@inheritdoc}
   */
  function invalidateEventTypes() {
    $event_types = $this->getEventTypes();
    foreach ($event_types as $i => $bundles) {
      foreach ($bundles as $b => $event_type) {
        /** @var \Drupal\rng\EventTypeInterface $event_type */
        $this->invalidateEventType($event_type);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  function invalidateEventType(EventTypeInterface $event_type) {
    Cache::invalidateTags($event_type->getCacheTags());
  }

}
