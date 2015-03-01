<?php

/**
 * @file
 * Contains \Drupal\rng\RegistrantInterface.
 */

namespace Drupal\rng;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface defining a Registrant entity.
 */
interface RegistrantInterface extends ContentEntityInterface {

  /**
   * Get associated identity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|NULL
   *   An entity, or NULL if the identity does not exist.
   */
  public function getIdentity();

  /**
   * Get associated identity entity keys.
   *
   * @return array|NULL
   *   An array with the keys entity_type and entity_id, or NULL if the identity
   *   does not exist.
   */
  public function getIdentityId();

  /**
   * Set associated identity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\rng\RegistrantInterface
   *   Returns registrant for chaining.
   */
  public function setIdentity(EntityInterface $entity);

  /**
   * Checks if the identity is the registrant.
   *
   * @return boolean
   *   Whether the identity is the registrant.
   */
  public function hasIdentity(EntityInterface $entity);
}