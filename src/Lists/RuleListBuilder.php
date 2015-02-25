<?php

/**
 * @file
 * Contains \Drupal\rng\Lists\RuleListBuilder.
 */

namespace Drupal\rng\Lists;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Builds a list of rng rules.
 */
class RuleListBuilder extends EntityListBuilder {
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
    $render['table'] = parent::render();
    $render['table']['#empty'] = t('No rules found for this event.');
    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    if (isset($this->event)) {
      $rule_ids = \Drupal::entityQuery('rng_rule')
        ->condition('event__target_type', $this->event->getEntityTypeId(), '=')
        ->condition('event__target_id', $this->event->id(), '=')
        ->execute();
      return $this->storage->loadMultiple($rule_ids);
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
    $header['id'] = t('id');
    $header['trigger'] = t('Trigger ID');
    $header['conditions'] = t('Conditions');
    $header['actions'] = t('Actions');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['trigger'] = $entity->getTriggerID();

    $conditions = array();
    $row['conditions']['data'] = array(
      '#theme' => 'links',
      '#links' => $conditions
    );

    $actions = array();
    foreach ($entity->getActions() as $action) {
      $actions[] = array(
        'title' => $this->t('Edit @action_id', array('@action_id' => $action->id())),
        'weight' => 10,
        'url' => $action->urlInfo('edit-form'),
      );
    }
    $row['actions']['data'] = array(
      '#theme' => 'links',
      '#links' => $actions
    );
    return $row;
  }
}