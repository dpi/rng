<?php

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

    $data['registrant']['table']['join']['registration_field_data'] = [
      'left_field' => 'id',
      'field' => 'registration',
    ];

    return $data;
  }

}
