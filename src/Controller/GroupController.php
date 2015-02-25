<?php

/**
 * @file
 * Contains \Drupal\rng\Controller\GroupController.
 */

namespace Drupal\rng\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Controller for Registration Groups.
 */
class GroupController extends ControllerBase implements ContainerInjectionInterface {
  /**
   * Provides a group add form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The matched route.
   *
   * @param string $event
   *   The parameter to find the event entity.
   *
   * @return array A registration form.
   */
  public function GroupAdd(RouteMatchInterface $route_match, $event) {
    $event = $route_match->getParameter($event);
    $group = $this->entityManager()
      ->getStorage('registration_group')
      ->create(array());
    $group->setEvent($event);
    return $this->entityFormBuilder()->getForm($group, 'add', array($event));
  }

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