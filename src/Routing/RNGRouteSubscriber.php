<?php

/**
 * @file
 * Contains \Drupal\rng\Routing\RNGRouteSubscriber.
 */

namespace Drupal\rng\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dynamic RNG routes.
 */
class RNGRouteSubscriber extends RouteSubscriberBase {
  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach (entity_load_multiple('event_type_config') as $event_type_config) {
      $entity_type = $this->entityManager->getDefinition($event_type_config->entity_type);
      if ($canonical_path = $entity_type->getLinkTemplate('canonical')) {
        // Manage Event
        $route = new Route(
          $canonical_path . '/event',
          array(
            '_form' => '\Drupal\rng\Form\EventSettingsForm',
            '_title' => 'Manage event',
            // Tell controller which parameter the event entity is stored.
            'event' => $event_type_config->entity_type,
          ),
          array(
            '_rng_event' => 'TRUE',
            '_rng_event_manage' => 'TRUE',
          ),
          array(
            'parameters' => array(
              $event_type_config->entity_type => array(
                'type' => 'entity:' . $event_type_config->entity_type,
              ),
            ),
          )
        );
        $collection->add("rng.event." . $event_type_config->entity_type . ".event", $route);

        // Register
        $route = new Route(
          $canonical_path . '/register',
          array(
            '_controller' => '\Drupal\rng\Controller\RNGController::RegistrationAddPage',
            '_title' => 'Register',
            'event' => $event_type_config->entity_type,
          ),
          array(
            '_rng_event' => 'TRUE',
            '_registrations_allowed' => 'TRUE',
            // @todo '_user_can_register_for_event'
          ),
          array(
            'parameters' => array(
              $event_type_config->entity_type => array(
                'type' => 'entity:' . $event_type_config->entity_type,
              ),
            ),
          )
        );
        $collection->add("rng.event." . $event_type_config->entity_type . ".register.type_list", $route);

        // Register w/ Registration Type
        $route = new Route(
          $canonical_path . '/register/{registration_type}',
          array(
            '_controller' => '\Drupal\rng\Controller\RNGController::RegistrationAdd',
            '_title_callback' => '\Drupal\rng\Controller\RNGController::addPageTitle',
            'event' => $event_type_config->entity_type,
          ),
          array(
            '_rng_event' => 'TRUE',
            '_registrations_allowed' => 'TRUE',
            '_event_registration_type' => 'TRUE',
          ),
          array(
            'parameters' => array(
              $event_type_config->entity_type => array(
                'type' => 'entity:' . $event_type_config->entity_type,
              ),
              'registration_type' => array(
                'type' => 'entity:registration_type',
              ),
            ),
          )
        );
        $collection->add("rng.event." . $event_type_config->entity_type . ".register", $route);
      }
    }
  }
}