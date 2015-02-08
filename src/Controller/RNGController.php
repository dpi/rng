<?php

/**
 * @file
 * Contains \Drupal\rng\Controller\RNGController.
 */

namespace Drupal\rng\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\rng\RegistrationTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for RNG.
 */
class RNGController extends ControllerBase implements ContainerInjectionInterface {
  /**
   * Generates a list of registration types for an event.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The matched route.
   *
   * @param string $event
   *   The parameter to find the event entity.
   *
   * @return array A registration form.
   */
  public function RegistrationAddPage(RouteMatchInterface $route_match, $event) {
    $render = array();
    $render[]['#markup'] = '<p>' . t('Select registration type:') . '</p>';
    $event_entity = $route_match->getParameter($event);
    $registration_types = array();
    foreach ($event_entity->{RNG_FIELD_EVENT_TYPE_REGISTRATION_TYPE} as $registration_type_item) {
      if ($registration_type = $registration_type_item->entity) {
        $registration_types[] = $registration_type;
        $url = new Url('rng.event.'. $event . '.register', array(
          $event => $event_entity->id(),
          'registration_type' => $registration_type->id(),
        ));

        $text = $this->l($registration_type->label(), $url);
        $text .= !empty($registration_type->description) ? "<p>$registration_type->description</p>" : '<br />';
        $render[]['#markup'] = $text;
      }
    }

    // Skip registration type display if there is only one.
    if (count($registration_types) == 1) {
      $registration_type = array_shift($registration_types);
      return $this->redirect('rng.event.'. $event . '.register', array(
        $event => $event_entity->id(),
        'registration_type' => $registration_type->id(),
      ));
    }

    return $render;
  }

  /**
   * Provides a registration form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The matched route.
   *
   * @param string $event
   *   The parameter to find the event entity.
   *
   * @param RegistrationTypeInterface $registration_type
   *   The type of registration.
   *
   * @return array A registration form.
   */
  public function RegistrationAdd(RouteMatchInterface $route_match, $event, RegistrationTypeInterface $registration_type) {
    $event_entity = $route_match->getParameter($event);
    $registration = $this->entityManager()
      ->getStorage('registration')
      ->create(array(
        'type' => $registration_type->id(),
      ));
    $registration->setEvent($event_entity);
    return $this->entityFormBuilder()->getForm($registration, 'add', array($event_entity));
  }

  /**
   * Title callback for registration.event.*.register
   *
   * @param \Drupal\rng\RegistrationTypeInterface
   *   The registration type.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(RegistrationTypeInterface $registration_type) {
    return $this->t('Create @label', array('@label' => $registration_type->label()));
  }
}