<?php

namespace Drupal\rng\Lists;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\rng\EventManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds a list of registrations.
 */
class RegistrationListBuilder extends EntityListBuilder {

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Row Counter.
   *
   * @var integer
   */
  protected $row_counter;

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
   * @param \Drupal\Core\Entity\EntityInterface $rng_event
   *   The event entity to display registrations.
   */
  public function render(EntityInterface $rng_event = NULL) {
    if (isset($rng_event)) {
      $this->event = $rng_event;
    }
    $render = parent::render();
    $render['table']['#empty'] = t('No registrations found for this event.');
    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    if (isset($this->event)) {
      return $this->eventManager->getMeta($this->event)->getRegistrations();
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
    $header['counter'] = '';
    $header['type'] = $this->t('Type');
    $header['groups'] = $this->t('Groups');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\rng\RegistrationInterface $entity
   *   A registration entity.
   */
  public function buildRow(EntityInterface $entity) {
    $row['counter'] = ++$this->row_counter;
    $bundle = entity_load($this->entityType->getBundleEntityType(), $entity->bundle());
    $row['type'] = $bundle ? $bundle->label() : '';

    $row['groups']['data'] = array(
      '#theme' => 'item_list',
      '#items' => [],
      '#attributes' => ['class' => ['inline']],
    );
    foreach ($entity->getGroups() as $group) {
      $text = '@group_label';
      $t_args = ['@group_id' => $group->id(), '@group_label' => $group->label()];
      $options['context'] = $group->isUserGenerated() ? 'system' : 'user';
      $row['groups']['data']['#items'][] = $this->t(
        $group->isUserGenerated() ? $text : "<em>$text</em>",
        $t_args,
        $options
      );
    }

    $row['created'] = format_date($entity->created->value);
    return $row + parent::buildRow($entity);
  }

}
