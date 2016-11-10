<?php

namespace Drupal\rng\Routing\Enhancer;

use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Route enhancer for RNG.
 */
class RngRouteEnhancer implements RouteEnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $route->hasRequirement('_entity_is_event') && $route->hasDefault('event');
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    $event_entity_type = $defaults['event'];

    if (isset($defaults[$event_entity_type])) {
      $rng_event = $defaults[$event_entity_type];
      $defaults['rng_event'] = $rng_event;
    }

    return $defaults;
  }

}
