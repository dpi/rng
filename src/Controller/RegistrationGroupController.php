<?php

/**
 * @file
 * Contains \Drupal\rng\Controller\RegistrationGroupController.
 */

namespace Drupal\rng\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Controller for Registration Groups.
 */
class RegistrationGroupController extends ControllerBase implements ContainerInjectionInterface {
  /**
   * Provides a list of registration groups for an event.
   *
   * @param string $event
   *   The parameter to find the event entity.
   */
  public function listing(RouteMatchInterface $route_match, $event) {
    $event_entity = $route_match->getParameter($event);
    return $this->entityManager()->getListBuilder('registration_group')->render($event_entity);
  }
}