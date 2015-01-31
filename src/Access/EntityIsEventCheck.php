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
    $parameters = $route_match->getParameters();

    if (($event_parameter_name = $route->getOption('_event_parameter')) && $parameters->has($event_parameter_name)) {
      $event_entity = $parameters->get($event_parameter_name);
      if (rng_entity_bundle($event_entity->getEntityTypeId(), $event_entity->bundle())) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::neutral();
  }
}