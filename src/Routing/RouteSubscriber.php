<?php

namespace Drupal\rng\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\rng\EventManagerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dynamic RNG routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, EventManagerInterface $event_manager) {
    $this->entityManager = $entity_manager;
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $event_types = $this->eventManager->getEventTypes();
    foreach (array_keys($event_types) as $entity_type) {
      $definition = $this->entityManager->getDefinition($entity_type);
      if ($canonical_path = $definition->getLinkTemplate('canonical')) {
        $manage_requirements = [
          '_entity_access' => $entity_type . '.manage event',
          '_entity_is_event' => 'TRUE',
        ];

        $options = [];
        // Option will invoke EntityConverter ParamConverter to upcast the
        // entity in $canonical_path.
        $options['parameters'][$entity_type]['type'] = 'entity:' . $entity_type;
        $options_register = $options;

        // Register tabs are not administrative.
        $options['_admin_route'] = 'TRUE';

        // Manage Event.
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

        // Access.
        $route = new Route(
          $canonical_path . '/event/access',
          [
            '_form' => '\Drupal\rng\Form\EventAccessForm',
            '_title' => 'Access',
            'event' => $entity_type,
          ],
          $manage_requirements,
          $options
        );
        $collection->add("rng.event.$entity_type.access", $route);

        // Reset access to default.
        $route = new Route(
          $canonical_path . '/event/access/reset',
          array(
            '_form' => '\Drupal\rng\Form\EventAccessResetForm',
            '_title' => 'Reset access to default',
            'event' => $entity_type,
          ),
          $manage_requirements + [
            '_event_rule_reset' => 'TRUE',
          ],
          $options
        );
        $collection->add("rng.event.$entity_type.access.reset", $route);

        // Messages.
        $route = new Route(
          $canonical_path . '/event/messages',
          array(
            '_form' => '\Drupal\rng\Form\MessageListForm',
            '_title' => 'Messages',
            'event' => $entity_type,
          ),
          $manage_requirements,
          $options
        );
        $collection->add("rng.event.$entity_type.messages", $route);

        // Message add.
        $route = new Route(
          $canonical_path . '/event/messages/add',
          array(
            '_form' => '\Drupal\rng\Form\MessageActionForm',
            '_title' => 'Add message',
            'event' => $entity_type,
          ),
          $manage_requirements,
          $options
        );
        $collection->add("rng.event.$entity_type.messages.add", $route);

        // Group list.
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

        // Group add.
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

        // Register.
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
          $options_register
        );
        $collection->add("rng.event.$entity_type.register.type_list", $route);

        // Register w/ Registration Type.
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
