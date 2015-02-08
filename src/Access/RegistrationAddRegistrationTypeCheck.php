<?php

/**
 * @file
 * Contains \Drupal\rng\Access\RegistrationAddRegistrationTypeCheck.
 */

namespace Drupal\rng\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks if registration type is valid for an event.
 */
class RegistrationAddRegistrationTypeCheck implements AccessInterface {
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    if (($event_parameter = $route->getDefault('event'))) {
      $event = $route_match->getParameter($event_parameter);
      $registration_type = $route_match->getParameter('registration_type');
      $registration_types = array_map(function($element){
        return $element['target_id'];
      }, $event->{RNG_FIELD_EVENT_TYPE_REGISTRATION_TYPE}->getValue());
      if (in_array($registration_type->id(), $registration_types)) {
        return AccessResult::allowed();
      };
    }
    return AccessResult::forbidden();
  }
}