<?php

/**
 * @file
 * Contains \Drupal\rng\Lists\RegistrationGroupListBuilder.
 */

namespace Drupal\rng\Lists;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Builds a list of registration groups.
 */
class RegistrationGroupListBuilder extends EntityListBuilder {
  /**
   * The event entity.
   *
   * @var EntityInterface
   */
  protected $event;

  /**
   * {@inheritdoc}
   *
   * @param EntityInterface $event
   *   The event entity to display registrations.
   */
  public function render(EntityInterface $event = NULL) {
    if (isset($event)) {
      $this->event = $event;
    }
    $render['temp'] = array('#markup' => '<br />'); // Secondary tabs fix: https://www.drupal.org/node/2426553
    $render['table'] = parent::render();
    $render['table']['#empty'] = t('No groups found for this event.');
    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    if (isset($this->event)) {
      $group_ids = \Drupal::entityQuery('registration_group')
        ->condition('event__target_type', $this->event->getEntityTypeId(), '=')
        ->condition('event__target_id', $this->event->id(), '=')
        ->execute();
      return $this->storage->loadMultiple($group_ids);
    }
    return parent::load();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->access('view') && $entity->hasLinkTemplate('canonical')) {
      $operations['view'] = array(
        'title' => $this->t('View'),
        'weight' => 0,
        'url' => $entity->urlInfo('canonical'),
      );
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Label');
    $header['description'] = t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['description'] = $entity->getDescription();
    return $row + parent::buildRow($entity);
  }
}