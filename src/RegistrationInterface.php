<?php

/**
 * @file
 * Contains \Drupal\rng\RegistrationInterface.
 */

namespace Drupal\rng;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface defining a Registration entity.
 */
interface RegistrationInterface extends ContentEntityInterface {
  /**
   * Gets information about the event entity.
   *
   * @return array|null
   *   An array containing values keyed with 'entity_type' and 'entity_id', or
   *   NULL if event ID is malformed.
   */
  public function getEventEntityInfo();

  /**
   * Set associated event.
   *
   * @param EntityInterface $entity
   *
   * @return \Drupal\rng\RegistrationInterface
   *   Returns registration for chaining.
   */
  public function setEvent(EntityInterface $entity);


  /**
   * Get associated event.
   *
   * @return EntityInterface $entity|NULL
   *   An entity, or NULL if the event does not exist.
   */
  public function getEvent();
}