<?php

namespace Drupal\rng\Lists;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Builds a list of event config entities.
 */
class EventTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityManagerInterface $entity_manager, AccountInterface $current_user) {
    parent::__construct($entity_type, $storage);
    $this->entityManager = $entity_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\rng\EventTypeInterface $entity **/
    $operations = parent::getDefaultOperations($entity);

    if ($this->moduleHandler->moduleExists('field_ui')) {
      $entity_type = \Drupal::entityTypeManager()
        ->getDefinition($entity->getEventEntityTypeId());

      if ($entity_type->get('field_ui_base_route')) {
        $options = [];
        if ($entity_type->getBundleEntityType() !== 'bundle') {
          $options[$entity_type->getBundleEntityType()] = $entity->getEventBundle();
        }
        $operations['manage-fields'] = [
          'title' => t('Event setting defaults'),
          'weight' => 15,
          'url' => Url::fromRoute("entity." . $entity->getEventEntityTypeId() . ".field_ui_fields", $options),
        ];
      }
    }

    return $operations;
  }

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
    /** @var \Drupal\rng\EventTypeInterface $entity **/

    $entity_type = $this->entityManager
      ->getDefinition($entity->getEventEntityTypeId());
    $t_args = ['@entity_type' => $entity_type->getLabel()];
    $bundle_entity_type = $entity_type->getBundleEntityType();
    if ($bundle_entity_type && $bundle_entity_type !== 'bundle') {
      $bundle = $this->entityManager
        ->getStorage($entity_type->getBundleEntityType())
        ->load($entity->getEventBundle());
      $t_args['@bundle'] = $bundle->label();
      $row['machine_name'] = $this->t('@entity_type: @bundle', $t_args);
    }
    else {
      // Entity type does not use bundles.
      $row['machine_name'] = $this->t('@entity_type', $t_args);
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $render = parent::render();
    $render['table']['#empty'] = t('No event types found.');
    return $render;
  }

}
