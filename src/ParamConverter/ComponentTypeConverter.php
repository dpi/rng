<?php

namespace Drupal\rng\ParamConverter;

use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\ParamConverterInterface;

/**
 * Provides upcasting for RNG event type rules components.
 */
class ComponentTypeConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if ($definition['type'] == 'rng_component_type') {
      return in_array($value, ['condition', 'action']) ? $value : NULL;
    }
    else if ($definition['type'] == 'rng_component_id') {
      $event_type_rule = $defaults['rng_event_type_rule'];
      $component_type = $defaults['component_type'];

      $components = [];
      if ($component_type == 'condition') {
        $components = $event_type_rule->getConditions();
      }
      else if ($component_type == 'action') {
        $components = $event_type_rule->getActions();
      }

      if (in_array($value, array_keys($components))) {
        return $value;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && in_array($definition['type'], ['rng_component_type', 'rng_component_id']));
  }

}
