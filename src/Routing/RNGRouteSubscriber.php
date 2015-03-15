<?php

/**
 * @file
 * Contains \Drupal\rng\Routing\RNGRouteSubscriber.
 */

namespace Drupal\rng\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

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
    $entity_type_config = array();
    foreach (entity_load_multiple('event_type_config') as $entity) {
      $entity_type_config[$entity->entity_type] = $entity->entity_type;
    }

    foreach ($entity_type_config as $entity_type) {
      $definition = $this->entityManager->getDefinition($entity_type);
      if ($canonical_path = $definition->getLinkTemplate('canonical')) {
        $manage_requirements = array('_entity_access' => $entity_type . '.manage event');
        $options = [];
        $options['parameters'][$entity_type]['type'] = 'entity:' . $entity_type;

        // Manage Event
        $route = new Route(
          $canonical_path . '/event',
          array(
            '_form' => '\Drupal\rng\Form\EventSettingsForm',
            '_title' => 'Manage event',
            // Tell controller which parameter the event entity is stored.
            'event' => $entity_type,
          ),
          $manage_requirements,
          $options
        );
        $collection->add("rng.event.$entity_type.event", $route);

        // Access
        $route = new Route(
          $canonical_path . '/event/access',
          array(
            '_controller' => '\Drupal\rng\Controller\EventController::listing_access',
            '_title' => 'Access',
            'event' => $entity_type,
          ),
          $manage_requirements,
          $options
        );
        $collection->add("rng.event.$entity_type.access", $route);

        // Reset access to default
        $route = new Route(
          $canonical_path . '/event/access/reset',
          array(
            '_form' => '\Drupal\rng\Form\EventAccessResetForm',
            '_title' => 'Reset access to default',
            'event' => $entity_type,
          ),
          $manage_requirements,
          $options
        );
        $collection->add("rng.event.$entity_type.access.reset", $route);

        // Rules
        $route = new Route(
          $canonical_path . '/event/rules',
          array(
            '_controller' => '\Drupal\rng\Controller\RuleController::listing',
            '_title' => 'Rules',
            'event' => $entity_type,
          ),
          $manage_requirements,
          $options
        );
        $collection->add("rng.event.$entity_type.rules", $route);

        // Messages
        $route = new Route(
          $canonical_path . '/event/messages',
          array(
            '_controller' => '\Drupal\rng\Controller\EventController::listing_messages',
            '_title' => 'Messages',
            'event' => $entity_type,
          ),
          $manage_requirements,
          $options
        );
        $collection->add("rng.event.$entity_type.messages", $route);

        // Message send
        $route = new Route(
          $canonical_path . '/event/messages/send',
          array(
            '_form' => '\Drupal\rng\Form\MessageActionForm',
            '_title' => 'Send message',
            'event' => $entity_type,
          ),
          $manage_requirements,
          $options
        );
        $collection->add("rng.event.$entity_type.messages.send", $route);

        // Group list
        $route = new Route(
          $canonical_path . '/event/groups',
          array(
            '_controller' => '\Drupal\rng\Controller\GroupController::listing',
            '_title' => 'Groups',
            'event' => $entity_type,
          ),
          $manage_requirements,
          $options
        );
        $collection->add("rng.event.$entity_type.group.list", $route);

        // Group add
        $route = new Route(
          $canonical_path . '/event/groups/add',
          array(
            '_controller' => '\Drupal\rng\Controller\GroupController::GroupAdd',
            '_title' => 'Add group',
            'event' => $entity_type,
          ),
          $manage_requirements,
          $options
        );
        $collection->add("rng.event.$entity_type.group.add", $route);

        // Registration list
        $route = new Route(
          $canonical_path . '/registrations',
          array(
            '_controller' => '\Drupal\rng\Controller\RegistrationController::listing',
            '_title' => 'Registrations',
            'event' => $entity_type,
          ),
          $manage_requirements,
          $options
        );
        $collection->add("rng.event.$entity_type.registrations", $route);

        // Register
        $route = new Route(
          $canonical_path . '/register',
          array(
            '_controller' => '\Drupal\rng\Controller\RegistrationController::RegistrationAddPage',
            '_title' => 'Register',
            'event' => $entity_type,
          ),
          array(
            '_registration_add_access' => 'TRUE',
          ),
          $options
        );
        $collection->add("rng.event.$entity_type.register.type_list", $route);

        // Register w/ Registration Type
        $options_register = $options;
        $options_register['parameters']['registration_type']['type'] = 'entity:registration_type';
        $route = new Route(
          $canonical_path . '/register/{registration_type}',
          array(
            '_controller' => '\Drupal\rng\Controller\RegistrationController::RegistrationAdd',
            '_title_callback' => '\Drupal\rng\Controller\RegistrationController::addPageTitle',
            'event' => $entity_type,
          ),
          array(
            '_registration_add_access' => 'TRUE',
          ),
          $options_register
        );
        $collection->add("rng.event.$entity_type.register", $route);
      }
    }
  }

}