<?php

namespace Drupal\rng\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\rng\EventManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\rng\RegistrationTypeInterface;
use Drupal\rng\Entity\Registration;

/**
 * Controller for registration entities.
 */
class RegistrationController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new registration controller.
   *
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(EventManagerInterface $event_manager) {
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('rng.event_manager')
    );
  }

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
    $event_entity = $route_match->getParameter($event);
    $render = [];
    $registration_types = $this->eventManager->getMeta($event_entity)->getRegistrationTypes();
    if (count($registration_types) == 1) {
      $registration_type = array_shift($registration_types);
      return $this->redirect('rng.event.' . $event . '.register', [
        $event => $event_entity->id(),
        'registration_type' => $registration_type->id(),
      ]);
    }
    else {
      $label = \Drupal::entityTypeManager()->getDefinition('registration_type')
        ->getLabel();
      $render['links'] = array(
        '#title' => $this->t('Select @entity_type', [
          '@entity_type' => $label,
        ]),
        '#theme' => 'item_list',
        '#items' => [],
      );
    }

    foreach ($registration_types as $registration_type) {
      $item = [];
      $url = new Url('rng.event.' . $event . '.register', [
        $event => $event_entity->id(),
        'registration_type' => $registration_type->id(),
      ]);
      $item[] = [
        '#type' => 'link',
        '#title' => $registration_type->label(),
        '#url' => $url,
        '#prefix' => '<h3>',
        '#suffix' => '</h3>',
      ];
      if (!empty($registration_type->description)) {
        $item[] = ['#markup' => $registration_type->description];
      }
      $render['links']['#items'][] = $item;
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
   * @param \Drupal\rng\RegistrationTypeInterface $registration_type
   *   The type of registration.
   *
   * @return array A registration form.
   */
  public function RegistrationAdd(RouteMatchInterface $route_match, $event, RegistrationTypeInterface $registration_type) {
    $event_entity = $route_match->getParameter($event);
    $registration = Registration::create([
      'type' => $registration_type->id(),
    ]);
    $registration->setEvent($event_entity);
    return $this->entityFormBuilder()->getForm($registration, 'add', array($event_entity));
  }

  /**
   * Title callback for registration.event.*.register.
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
