<?php

/**
 * @file
 * Contains \Drupal\rng\Controller\RNGController.
 */

namespace Drupal\rng\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

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
   * @return array A registration form.
   * A registration form.
   */
  public function add_registration(RouteMatchInterface $route_match) {
    $parameters = $route_match->getParameters();
    $route = $route_match->getRouteObject();

    if (($event_parameter_name = $route->getOption('_event_parameter')) && $parameters->has($event_parameter_name)) {
      $event_entity = $parameters->get($event_parameter_name);
      $registration_type = $event_entity->{RNG_FIELD_EVENT_TYPE_REGISTRATION_TYPE}->target_id;
      $registration = $this->entityManager()->getStorage('registration')->create(array(
        'type' => $registration_type,
      ));
      $registration->setEvent($event_entity);

      return $this->entityFormBuilder()->getForm($registration, 'add', array($event_entity));
    }

    // @todo: handle condition where no event entity was passed
  }
}