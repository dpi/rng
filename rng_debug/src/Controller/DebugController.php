<?php

/**
 * @file
 * Contains \Drupal\rng_debug\Controller\DebugController.
 */

namespace Drupal\rng_debug\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Controller for rng_debug.
 */
class DebugController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Provides a list of rng rules for an event.
   *
   * @param string $event
   *   The parameter to find the event entity.
   */
  public function listing(RouteMatchInterface $route_match, $event) {
    $event_entity = $route_match->getParameter($event);
    return $this->entityManager()->getListBuilder('rng_rule')->render($event_entity);
  }

}
