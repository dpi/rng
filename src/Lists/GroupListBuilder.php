<?php

/**
 * @file
 * Contains \Drupal\rng\Lists\GroupListBuilder.
 */

namespace Drupal\rng\Lists;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Mail\MailFormatHelper;

/**
 * Builds a list of registration groups.
 */
class GroupListBuilder extends EntityListBuilder {
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
    $render['description'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('Groups allow you to organize registrations. Some pre-made groups are automatically applied to registrations.'),
      '#suffix' => '</p>',
    ];

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
  public function buildHeader() {
    $header['label'] = t('Label');
    $header['source'] = t('Source');
    $header['description'] = t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['source'] = $entity->isUserGenerated() ? t('User') : t('System');
    $row['description'] = MailFormatHelper::htmlToText($entity->getDescription());
    return $row + parent::buildRow($entity);
  }
}