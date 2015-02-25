<?php

/**
 * @file
 * Contains \Drupal\rng\RegistrationInterface.
 */

namespace Drupal\rng;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a Registration entity.
 */
interface RegistrationInterface extends ContentEntityInterface {
  /**
   * Get associated event.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|NULL
   *   An entity, or NULL if the event does not exist.
   */
  public function getEvent();

  /**
   * Set associated event.
   *
   * @param EntityInterface $entity
   *
   * @return \Drupal\rng\RegistrationInterface
   *   Returns registration for chaining.
   */
  public function setEvent(ContentEntityInterface $entity);

  /**
   * Get registrants for the registration.
   *
   * @return RegistrantInterface[]
   *   An array of registrant entities.
   */
  public function getRegistrants();
}