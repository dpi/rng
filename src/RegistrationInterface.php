<?php

namespace Drupal\rng;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface defining a Registration entity.
 */
interface RegistrationInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Get associated event.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|NULL
   *   An entity, or NULL if the event does not exist.
   */
  public function getEvent();

  /**
   * Returns the registration creation timestamp.
   *
   * @return int
   *   Creation timestamp of the registration.
   */
  public function getCreatedTime();

  /**
   * Sets the registration creation timestamp.
   *
   * @param int $timestamp
   *   The registration creation timestamp.
   *
   * @return \Drupal\rng\RegistrationInterface
   *   Returns registration for chaining.
   */
  public function setCreatedTime($timestamp);

  /**
   * Set associated event.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
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
   * @return \Drupal\rng\RegistrantInterface[]
   *   An array of registrant entities.
   */
  public function getRegistrants();

  /**
   * Searches registrants on this registration for an identity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $identity
   *   The identity to search.
   *
   * @return boolean
   *   Whether the identity is a registrant.
   */
  public function hasIdentity(EntityInterface $identity);

  /**
   * Shortcut to add a registrant entity.
   *
   * Take care to ensure the identity is not already on the registration.
   *
   * @param \Drupal\Core\Entity\EntityInterface $identity
   *   The identity to add.
   *
   * @return \Drupal\rng\RegistrationInterface
   *   Returns registration for chaining.
   */
  public function addIdentity(EntityInterface $identity);

  /**
   * Get groups for the registration.
   *
   * @return \Drupal\rng\GroupInterface[]
   *   An array of registration_group entities.
   */
  public function getGroups();

  /**
   * Add a group to the registration.
   *
   * @param \Drupal\rng\GroupInterface $group
   *   The group to add.
   *
   * @return \Drupal\rng\RegistrationInterface
   *   Returns registration for chaining.
   */
  public function addGroup(GroupInterface $group);

  /**
   * Remove a group from the registration.
   *
   * @param int $group_id
   *   The ID of a registration_group entity.
   *
   * @return \Drupal\rng\RegistrationInterface
   *   Returns registration for chaining.
   */
  public function removeGroup($group_id);

}
