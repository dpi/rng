<?php

namespace Drupal\rng;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\courier\ChannelInterface;
use Drupal\courier\Entity\TemplateCollection;
use Drupal\courier\TemplateCollectionInterface;

/**
 * RNG entity access.
 */
class RngEntityAccess {

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new RngEntityAccess object.
   *
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(EventManagerInterface $event_manager) {
    $this->eventManager = $event_manager;
  }

  /**
   * Control entity operation access.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check access to.
   * @param string $operation
   *   The operation that is to be performed on $entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see hook_entity_access();
   */
  public function hook_entity_access(EntityInterface $entity, $operation, AccountInterface $account) {
    if (('manage event' == $operation) && $this->eventManager->isEvent($entity)) {
      return $this->manageEventAccess($entity, $account);
    }

    if (('update' == $operation) && $entity instanceof ChannelInterface) {
      return $this->updateCourierMessageAccess($entity, $account);
    }

    if (('templates' == $operation) && $entity instanceof TemplateCollectionInterface) {
      return $this->templateCollectionTemplateAccess($entity, $account);
    }

    return AccessResult::neutral();
  }

  /**
   * Whether the account is permitted to manage event.
   *
   * This method is a proxy for a different permission name.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check access to.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function manageEventAccess($entity, AccountInterface $account) {
    $event_type = $this->eventManager
      ->eventType($entity->getEntityTypeId(), $entity->bundle());
    $manage_operation = $event_type->getEventManageOperation();

    // Prevents recursion:
    if (!empty($manage_operation) && ('manage event' != $manage_operation)) {
      if ($entity->access($manage_operation, $account)) {
        return AccessResult::allowed()
          ->addCacheableDependency($entity);
      }
    }

    return AccessResult::neutral();
  }

  /**
   * Allow editing template if the user has 'manage event' for the event.
   *
   * @param \Drupal\courier\ChannelInterface $entity
   *   A Courier template entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function updateCourierMessageAccess(ChannelInterface $entity, AccountInterface $account) {
    $template_collection = TemplateCollection::getTemplateCollectionForTemplate($entity);
    if ($template_collection) {
      $owner = $template_collection->getOwner();
      if ($owner && $this->eventManager->isEvent($owner)) {
        return AccessResult::allowedIf($owner->access('manage event', $account))
          ->addCacheableDependency($owner);
      }
    }

    return AccessResult::neutral();
  }

  /**
   * Determine whether the account can edit templates for a template collection.
   *
   * @param \Drupal\courier\TemplateCollectionInterface $entity
   *   A Courier template collection entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function templateCollectionTemplateAccess(TemplateCollectionInterface $entity, AccountInterface $account) {
    $owner = $entity->getOwner();
    if ($owner && $this->eventManager->isEvent($owner)) {
      return AccessResult::allowedIf($owner->access('manage event', $account))
        ->addCacheableDependency($owner);
    }

    return AccessResult::neutral();
  }

}
