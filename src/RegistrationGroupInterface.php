<?php

/**
 * @file
 * Contains \Drupal\rng\RegistrationGroupInterface.
 */

namespace Drupal\rng;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface defining a registration group entity.
 */
interface RegistrationGroupInterface extends ContentEntityInterface {
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
   *   Returns group for chaining.
   */
  public function setEvent(ContentEntityInterface $entity);

  /**
   * Returns the description.
   *
   * @return string
   *   Description of the registration group.
   */
  public function getDescription();

  /**
   * Sets the description.
   *
   * @param string $description
   *   The description.
   *
   * @return \Drupal\rng\RegistrationGroupInterface
   *   Returns group for chaining.
   */
  public function setDescription($description);
}