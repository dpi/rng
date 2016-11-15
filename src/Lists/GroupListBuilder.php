<?php

namespace Drupal\rng\Lists;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\rng\EventManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds a list of registration groups.
 */
class GroupListBuilder extends EntityListBuilder {

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

    $render['description'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('Groups allow you to organize registrations. Some pre-made groups are automatically applied to registrations.'),
      '#suffix' => '</p>',
      '#weight' => -50,
    ];

    $render['table']['#empty'] = t('No groups found for this event.');
    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    if (isset($this->event)) {
      return $this->eventManager->getMeta($this->event)->getGroups();
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
