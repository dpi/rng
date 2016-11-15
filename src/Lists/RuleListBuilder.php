<?php

namespace Drupal\rng\Lists;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\rng\EventManagerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
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
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The event entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $event;

  /**
   * Constructs a new RegistrationListBuilder object.
   *
   * {@inheritdoc}
   *
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EventManagerInterface $event_manager, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($entity_type, $storage);
    $this->eventManager = $event_manager;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('rng.event_manager'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityInterface $rng_event
   *   The event entity to display registrations.
   */
  public function render(EntityInterface $rng_event = NULL) {
    if (isset($rng_event)) {
      $this->event = $rng_event;
    }
    drupal_set_message($this->t('This rule list is for advanced users. Take care when committing any actions from this page.'), 'warning');
    $render = parent::render();
    $render['table']['#empty'] = t('No rules found for this event.');
    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    if (isset($this->event)) {
      return $this->eventManager->getMeta($this->event)->getRules(NULL, FALSE, NULL);
    }
    return parent::load();
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    foreach ($operations as &$operation) {
      $operation['query'] = $this->redirectDestination->getAsArray();
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
    $header['status'] = t('Status');
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
        'query' => $this->redirectDestination->getAsArray(),
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
        'query' => $this->redirectDestination->getAsArray(),
      );
    }

    $row['status'] = $entity->isActive() ? $this->t('Active') : $this->t('Inactive');

    return $row + parent::buildRow($entity);
  }

}
