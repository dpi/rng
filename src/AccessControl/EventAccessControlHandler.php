<?php

/**
 * @file
 * Contains \Drupal\rng\AccessControl\EventAccessControlHandler.
 */

namespace Drupal\rng\AccessControl;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the events and related entities.
 */
class EventAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    $account = $this->prepareUser($account);
    $child = $entity->getEntityTypeId() != 'rng_rule_component' ? $entity : $entity->getRule();
    if ($child instanceof EntityInterface) {
      return $child->getEvent()->access('manage event', $account, TRUE);
    }
    return AccessResult::neutral();
  }

}
