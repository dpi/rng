<?php

namespace Drupal\rng;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a registration group entity.
 */
interface GroupInterface extends ContentEntityInterface {

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
   * @param ContentEntityInterface $entity
   *
   * @return \Drupal\rng\RegistrationInterface
   *   Returns group for chaining.
   */
  public function setEvent(ContentEntityInterface $entity);

  /**
   * Determine if a module created the group.
   *
   * @return boolean
   *   Whether the group is user created.
   */
  public function isUserGenerated();

  /**
   * Get which module created the group.
   *
   * @return string
   *   Name of a module.
   */
  public function getSource();

  /**
   * Set which module created this group.
   *
   * @param string $module
   *   Name of a module.
   *
   * @return \Drupal\rng\GroupInterface
   *   Returns group for chaining.
   */
  public function setSource($module);

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
   * @return \Drupal\rng\GroupInterface
   *   Returns group for chaining.
   */
  public function setDescription($description);

  /**
   * Get required groups.
   *
   * Groups required for this group to be added to a registration.
   *
   * @return \Drupal\rng\GroupInterface[]
   *   Groups required for this group.
   */
  public function getDependentGroups();

  /**
   * Get conflicting groups.
   *
   * Groups which cannot exist on a registration for this for this group to be
   * added.
   *
   * @return \Drupal\rng\GroupInterface[]
   *   Groups that conflict with this group.
   */
  public function getConflictingGroups();

}
