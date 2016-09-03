<?php

/**
 * @file
 * Provide views data for RNG.
 */

/**
 * Implements hook_views_data_alter().
 */
function rng_views_data_alter(&$data) {
  $entity_type_manager = \Drupal::entityTypeManager();
  $event_manager = \Drupal::service('rng.event_manager');

  $event_types = $event_manager->getEventTypes();
  foreach (array_keys($event_types) as $entity_type) {
    $definition = $entity_type_manager->getDefinition($entity_type);
    if ($base_table = $definition->getBaseTable()) {
      $data[$base_table]['rng_event_register'] = [
        'field' => [
          'title' => t('Register for event'),
          'help' => t('Provides a link to register for the event.'),
          'id' => 'rng_event_register',
        ],
      ];
    }
  }

  return $data;
}
