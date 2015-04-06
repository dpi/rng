<?php

/**
 * @file
 * Contains \Drupal\rng\Lists\RuleListBuilder.
 */

namespace Drupal\rng\Lists;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\rng\EventManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds a list of rng rules.
 */
class RuleListBuilder extends EntityListBuilder {

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * The event entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $event;

  /**
   * Route to redirect after performing operations linked from this list.
   *
   * @var array
   */
  protected $destination;

  /**
   * Constructs a new RegistrationListBuilder object.
   *
   * {@inheritdoc}
   *
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EventManagerInterface $event_manager) {
    parent::__construct($entity_type, $storage);
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('rng.event_manager')
    );
  }

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
    $this->destination = drupal_get_destination();
    $render['description'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('This rule list is for debugging purposes. There are better lists found in the <strong>Access</strong> and <strong>Messages</strong> tabs.'),
      '#suffix' => '</p>',
    ];
    $render['table'] = parent::render();
    $render['table']['#empty'] = t('No rules found for this event.');
    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    if (isset($this->event)) {
      return $this->eventManager->getMeta($this->event)->getRules();
    }
    return parent::load();
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    foreach ($operations as &$operation) {
      $operation['query'] = $this->destination;
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
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\rng\RuleInterface $entity
   *   A rule entity.
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['trigger'] = $entity->getTriggerID();

    $row['conditions']['data'] = array(
      '#theme' => 'links',
      '#links' => [],
      '#attributes' => ['class' => ['links', 'inline']],
    );
    foreach ($entity->getConditions() as $condition) {
      $row['conditions']['data']['#links'][] = array(
        'title' => $this->t('Edit', ['@condition_id' => $condition->id(), '@condition' => $condition->getPluginId()]),
        'url' => $condition->urlInfo('edit-form'),
        'query' => $this->destination,
      );
    }

    $row['actions']['data'] = array(
      '#theme' => 'links',
      '#links' => [],
      '#attributes' => ['class' => ['links', 'inline']],
    );
    foreach ($entity->getActions() as $action) {
      $row['actions']['data']['#links'][] = array(
        'title' => $this->t('Edit', ['@action_id' => $action->id(), '@action' => $action->getPluginId()]),
        'url' => $action->urlInfo('edit-form'),
        'query' => $this->destination,
      );
    }

    return $row + parent::buildRow($entity);
  }

}
