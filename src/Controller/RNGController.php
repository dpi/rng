<?php

/**
 * @file
 * Contains \Drupal\rng\Controller\RNGController.
 */

namespace Drupal\rng\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for RNG.
 */
class RNGController extends ControllerBase implements ContainerInjectionInterface {
  /**
   * Provides a registration form for an event.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The matched route.
   *
   * @param string $event
   *   The parameter to find the event entity.
   *
   * @return array A registration form.
   */
  public function add_registration(RouteMatchInterface $route_match, $event) {
    $event_entity = $route_match->getParameter($event);
    $registration_type = $event_entity->{RNG_FIELD_EVENT_TYPE_REGISTRATION_TYPE}->target_id;
    $registration = $this->entityManager()
      ->getStorage('registration')
      ->create(array(
        'type' => $registration_type,
      ));
    $registration->setEvent($event_entity);
    return $this->entityFormBuilder()->getForm($registration, 'add', array($event_entity));
  }
}