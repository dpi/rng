<?php

/**
 * @file
 * Contains \Drupal\rng\Access\EntityIsEventCheck.
 */

namespace Drupal\rng\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use \Drupal\Core\Entity\EntityInterface;

/**
 * Checks that an entity is an event type.
 */
class EntityIsEventCheck implements AccessInterface {
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    if (($event = $route->getDefault('event')) && $event = $route_match->getParameter($event)) {
      if (rng_entity_bundle($event->getEntityTypeId(), $event->bundle())) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }
}