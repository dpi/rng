<?php

/**
 * @file
 * Contains \Drupal\rng\Controller\RuleController.
 */

namespace Drupal\rng\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Controller for RNG rules.
 */
class RuleController extends ControllerBase implements ContainerInjectionInterface {
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