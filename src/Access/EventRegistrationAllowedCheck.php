<?php

/**
 * @file
 * Contains \Drupal\rng\Access\EventRegistrationAllowedCheck.
 */

namespace Drupal\rng\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks new registrations are permitted on an event.
 */
class EventRegistrationAllowedCheck implements AccessInterface {
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    if (($event = $route->getDefault('event')) && $event = $route_match->getParameter($event)) {
      // @todo
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }
}