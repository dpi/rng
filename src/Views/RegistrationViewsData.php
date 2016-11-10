<?php

namespace Drupal\rng\Views;

use Drupal\views\EntityViewsData;

/**
 * Provides views data for registration entities.
 */
class RegistrationViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $registration_definition = \Drupal::entityTypeManager()->getDefinition('registration');
    $group_definition = \Drupal::entityTypeManager()->getDefinition('registration_group');

    // Add base field reference for groups.
    $t_args = [
      '@origin_label' => $registration_definition->getLabel(),
      '@target_label' => $group_definition->getLabel(),
    ];

    $data['registration__groups']['table']['group']  = $group_definition->getLabel();
    $data['registration__groups']['table']['join']['registration_field_data'] = [
      'left_field' => 'id',
      'field' => 'entity_id',
    ];

    $data['registration__groups']['groups_target_id'] = [
      'title' => t('Group ID'),
      'help' => t('A @target_labels group ID.', $t_args),
      'field' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'filter' => [
        'id' => 'numeric',
        'allow empty' => TRUE,
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $psuedo_field = 'rng_registration__registration_group';
    $data['registration__groups'][$psuedo_field]['relationship'] = [
      'title' => t('@target_labels', $t_args),
      'label' => t('@target_labels', $t_args),
      'group' => $registration_definition->getLabel(),
      'help' => t('References to the @target_labels of a @origin_label.', $t_args),
      'id' => 'standard',
      'base' => 'registration_group_field_data',
      'base field' => $group_definition->getKey('id'),
      'relationship field' => 'groups_target_id',
    ];

    return $data;
  }

}
