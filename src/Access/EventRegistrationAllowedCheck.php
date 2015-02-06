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
      if (empty($event->{RNG_FIELD_EVENT_TYPE_STATUS}->value)) {
        return AccessResult::forbidden();
      }

      $capacity = $event->{RNG_FIELD_EVENT_TYPE_CAPACITY}->value;
      if ($capacity != '' && is_numeric($capacity) && $capacity > -1) {
        $registration_count = \Drupal::entityQuery('registration')
          ->condition('event', $event->getEntityTypeId() . ':' . $event->id(), '=')
          ->count()
          ->execute();
        if ($registration_count >= $capacity) {
          return AccessResult::forbidden();
        }
      }

      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }
}