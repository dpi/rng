<?php

namespace Drupal\rng\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\rng\EventManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\Entity\EntityInterface;

/**
 * Checks that an entity is an event type.
 */
class EventRuleResetCheck implements AccessInterface {

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new EventRuleResetCheck object.
   *
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(EventManagerInterface $event_manager) {
    $this->eventManager = $event_manager;
  }

  /**
   * Checks that an entity is an event type.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $access = AccessResult::neutral();

    if ($event = $route->getDefault('event')) {
      $event = $route_match->getParameter($event);
      if ($event instanceof EntityInterface) {
        $event_type = $this->eventManager->eventType($event->getEntityTypeId(), $event->bundle());
        if ($event_type) {
          $event_meta = $this->eventManager->getMeta($event);
          // Allow custom rules |OR|
          // If not default rules, then allow event manager to reset back.
          if ($event_type->getAllowCustomRules() || !$event_meta->isDefaultRules('rng_event.register')) {
            $access = AccessResult::allowed();
          }
          $access->addCacheableDependency($event_type);
        }
      }
      $access->addCacheableDependency($event);
    }

    return $access;
  }

}
