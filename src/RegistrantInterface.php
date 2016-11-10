<?php

namespace Drupal\rng;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface defining a Registrant entity.
 */
interface RegistrantInterface extends ContentEntityInterface {

  /**
   * Get associated registration.
   *
   * @return \Drupal\rng\RegistrationInterface|NULL
   *   The parent registration, or NULL if it does not exist.
   */
  public function getRegistration();

  /**
   * Set associated registration.
   *
   * @param \Drupal\rng\RegistrationInterface $registration
   *   The new associated registration.
   *
   * @return \Drupal\rng\RegistrantInterface
   *   Returns registrant for chaining.
   */
  public function setRegistration(RegistrationInterface $registration);

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
   *   The identity to set.
   *
   * @return \Drupal\rng\RegistrantInterface
   *   Returns registrant for chaining.
   */
  public function setIdentity(EntityInterface $entity);

  /**
   * Removes identity associated with this registrant.
   *
   * @return \Drupal\rng\RegistrantInterface
   *   Returns registrant for chaining.
   */
  public function clearIdentity();

  /**
   * Checks if the identity is the registrant.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The identity to check is associated with this registrant.
   *
   * @return boolean
   *   Whether the identity is the registrant.
   */
  public function hasIdentity(EntityInterface $entity);

  /**
   * Get registrants belonging to an identity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $identity
   *   An identity entity.
   *
   * @return int[]
   *   An array of registrant entity IDs.
   */
  public static function getRegistrantsIdsForIdentity(EntityInterface $identity);

}
