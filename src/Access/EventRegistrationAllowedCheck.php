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
    $parameters = $route_match->getParameters();

    if (($event_entity_type = $route->getOption('_event_parameter')) && $parameters->has($event_entity_type)) {
      $event_entity = $parameters->get($event_entity_type);
      // @todo: Check if event is accepting new registrations
      return AccessResult::allowed();
    }

    return AccessResult::neutral();
  }
}