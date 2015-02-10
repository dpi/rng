<?php

/**
 * @file
 * Contains \Drupal\rng\RegistrationViewsData.
 */

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

    $data['registration']['table']['group'] = t('Registration table');

    $data['registration_field_data']['event']['label'] = t('Event');
    $data['registration_field_data']['created']['label'] = t('Created');
    $data['registration_field_data']['updated']['label'] = t('Updated');
    $data['registration_field_data']['status']['label'] = t('Status');

    return $data;
  }
}