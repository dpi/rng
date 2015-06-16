<?php

/**
 * @file
 * Contains \Drupal\rng\Lists\EventTypeListBuilder.
 */

namespace Drupal\rng\Lists;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Builds a list of event config entities.
 */
class EventTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['machine_name'] = $this->t('Event type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['machine_name'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

}
