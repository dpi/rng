<?php

namespace Drupal\rng\Lists;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Builds a list of registrant types.
 */
class RegistrantTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Registrant type');
    $header['machine_name'] = $this->t('Machine Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['machine_name'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $render = parent::render();
    $render['table']['#empty'] = t('No registrant types found.');
    return $render;
  }

}
