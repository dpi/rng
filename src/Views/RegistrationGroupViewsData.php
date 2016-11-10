<?php

namespace Drupal\rng\Views;

use Drupal\views\EntityViewsData;

/**
 * Provides views data for registration group entities.
 */
class RegistrationGroupViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Add base field reference for groups.
    $registration_definition = \Drupal::entityTypeManager()->getDefinition('registration');
    $group_definition = \Drupal::entityTypeManager()->getDefinition('registration_group');

    $t_args = [
      '@origin_label' => $group_definition->getLabel(),
      '@target_label' => $registration_definition->getLabel(),
    ];

    // Target ID can be NULL.
    $data['registration_group_field_data']['event__target_id']['filter']['allow empty'] = TRUE;

    // Reverse relationship (Target to Origin).
    $psuedo_field = 'rng_registration_group__registration';
    $data['registration_group_field_data'][$psuedo_field]['relationship'] = [
      'title' => t('@target_labels', $t_args),
      'label' => t('@target_labels', $t_args),
      'group' => $group_definition->getLabel(),
      'help' => t('References to the @target_labels of a @origin_label.', $t_args),
      'entity_type' => $registration_definition->id(),
      'id' => 'entity_reverse',
      'base' => $registration_definition->getDataTable() ?: $registration_definition->getBaseTable(),
      'base field' => $registration_definition->getKey('id'),
      'field_name' => 'groups',
      'field table' => 'registration__groups',
      'field field' => 'groups_target_id',
      'relationship field' => $group_definition->getKey('id'),
    ];

    return $data;
  }

}
