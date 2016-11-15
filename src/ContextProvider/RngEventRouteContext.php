<?php

namespace Drupal\rng\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\rng\EventManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityInterface;

/**
 * Get the current RNG event from the route.
 */
class RngEventRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new RngEventRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(RouteMatchInterface $route_match, EventManagerInterface $event_manager) {
    $this->routeMatch = $route_match;
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['rng_event']);

    $context_definition = new ContextDefinition('entity', NULL, FALSE);

    $context = new Context($context_definition, $this->getEventInRoute());
    $context->addCacheableDependency($cacheability);

    return ['rng_event' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('entity', $this->t('RNG event from route')));
    return ['rng_event' => $context];
  }

  /**
   * Determine the event in the current route.
   *
   * @return \Drupal\Core\Entity\EntityInterface|NULL
   *   The event entity, or NULL.
   */
  protected function getEventInRoute() {
    // Will be NULL in CLI.
    if (!$route = $this->routeMatch->getRouteObject()) {
      return NULL;
    }

    if ($event_param = $route->getDefault('event')) {
      $event = $this->routeMatch->getParameter($event_param);
      return $event instanceof EntityInterface ? $event : NULL;
    }

    if ($events = $this->getEventEntitiesInRoute()) {
      // There could be multiple events in a route, determine which event
      // is the correct one.
      foreach ($events as $entity) {
        // Exact link templates
        foreach ($entity->getEntityType()->getLinkTemplates() as $link_template => $path) {
          if ($route->getPath() === $path) {
            return $entity;
          }
        }

        // Or this route is a sub string of canonical.
        if ($canonical_path = $entity->getEntityType()->getLinkTemplate('canonical')) {
          if (strpos($route->getPath(), $canonical_path) === 0) {
            return $entity;
          }
        }
      }
    }

    return NULL;
  }

  /**
   * Get all event entities from the current route.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of event entities in route.
   */
  protected function getEventEntitiesInRoute() {
    $events = [];

    if (($route = $this->routeMatch->getRouteObject()) && ($parameters = $route->getOption('parameters'))) {
      foreach ($parameters as $parameter) {
        if (isset($parameter['type']) && strpos($parameter['type'], 'entity:') !== FALSE) {
          $entity_type_id = substr($parameter['type'], strlen('entity:'));
          $entity = $this->routeMatch->getParameter($entity_type_id);
          if ($entity instanceof EntityInterface && $this->eventManager->isEvent($entity)) {
            $events[] = $entity;
          }
        }
      }
    }

    return $events;
  }
}
