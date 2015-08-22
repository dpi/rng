<?php

/**
 * @file
 * Contains \Drupal\rng\Views\RegistrantViewsData.
 */

namespace Drupal\rng\Views;

use Drupal\views\EntityViewsData;

/**
 * Provides views data for registrant entities.
 */
class RegistrantViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $registration_definition = \Drupal::entityManager()->getDefinition('registration');
    $registrant_definition = \Drupal::entityManager()->getDefinition('registrant');

    $data['registrant']['table']['entity type']  = $registration_definition->id();
    $data['registrant']['table']['group']  = $registrant_definition->getLabel();
    $data['registrant']['table']['join']['registration_field_data'] = [
      'left_field' => 'id',
      'field' => 'registration',
    ];

    return $data;
  }

}
