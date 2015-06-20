<?php

/**
 * @file
 * Contains \Drupal\rng\EventTypeInterface.
 */

namespace Drupal\rng;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a event config entity.
 */
interface EventTypeInterface extends ConfigEntityInterface {

  /**
   * Get event entity type ID.
   *
   * @return string
   *   An entity type ID.
   */
  function getEventEntityTypeId();

  /**
   * Sets the event entity type ID.
   *
   * @param string $entity_type
   *   An entity type ID.
   */
  function setEventEntityTypeId($entity_type);

  /**
   * Get event bundle.
   *
   * @return string
   *   A bundle name.
   */
  function getEventBundle();

  /**
   * Sets the event bundle.
   *
   * @param string $bundle
   *   A bundle name.
   */
  function setEventBundle($bundle);

  /**
   * Gets which permission on event entity grants 'event manage' permission.
   */
  function getEventManageOperation();

  /**
   * Sets operation to mirror from the event entity.
   *
   * @param string $permission
   *   The operation to mirror.
   */
  function setEventManageOperation($permission);

  /**
   * Create or clean up courier_context if none exist for an entity type.
   *
   * @param string $entity_type
   *   Entity type of the event type.
   * @param string $operation
   *   An operation: 'create' or 'delete'.
   */
  static function courierContextCC($entity_type, $operation);

}
