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
   * Get registrants IDs for the registration.
   *
   * @return integer[]
   *   An array of registrant IDs.
   */
  public function getRegistrantIds();

  /**
   * Get registrants for the registration.
   *
   * @return RegistrantInterface[]
   *   An array of registrant entities.
   */
  public function getRegistrants();

  /**
   * Searches registrants on this registration for an identity.
   *
   * @return boolean
   *   Whether the identity is a registrant.
   */
  public function hasIdentity(EntityInterface $entity);
}