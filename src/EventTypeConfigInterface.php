<?php

/**
 * @file
 * Contains \Drupal\rng\EventTypeConfigInterface.
 */

namespace Drupal\rng;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a event config entity.
 */
interface EventTypeConfigInterface extends ConfigEntityInterface {

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
