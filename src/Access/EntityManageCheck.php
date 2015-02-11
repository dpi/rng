<?php

/**
 * @file
 * Contains \Drupal\rng\Access\EntityManageCheck.
 */

namespace Drupal\rng\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks if a user can edit an event.
 */
class EntityManageCheck implements AccessInterface {
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    if (($event = $route->getDefault('event')) && $event = $route_match->getParameter($event)) {
      $event_config = rng_entity_bundle($event->getEntityTypeId(), $event->bundle());
      if (!empty($event_config->mirror_update_permission)) {
        if ($event->access($event_config->mirror_update_permission, $account)) {
          return AccessResult::allowed();
        }
        else {
          return AccessResult::forbidden();
        }
      }
    }
    return AccessResult::neutral();
  }
}