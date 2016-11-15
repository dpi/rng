<?php

namespace Drupal\rng\AccessControl;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for groups.
 */
class GroupAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\rng\GroupInterface $entity
   *   A group entity.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $account = $this->prepareUser($account);
    $event = $entity->getEvent();

    if (!$entity->isUserGenerated() && $operation == 'delete') {
      return AccessResult::forbidden();
    }

    if ($event) {
      return $event->access('manage event', $account, TRUE);
    }

    return AccessResult::neutral();
  }

}
