<?php

/**
 * @file
 * Contains \Drupal\rng\Plugin\EntityReferenceSelection\UserRNGSelection.
 */

namespace Drupal\rng\Plugin\EntityReferenceSelection;

use Drupal\rng\Plugin\EntityReferenceSelection\RNGSelectionBase;
use Drupal\rng\RuleGrantsOperationTrait;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Connection;
use Drupal\rng\EventManagerInterface;
use Drupal\Core\Condition\ConditionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rng\RNGConditionInterface;

/**
 * Provides selection for user entities when registering.
 *
 * @EntityReferenceSelection(
 *   id = "rng:register:user",
 *   label = @Translation("User selection"),
 *   entity_types = {"user"},
 *   group = "rng_register",
 *   weight = 10
 * )
 */
class UserRNGSelection extends RNGSelectionBase {

  use RuleGrantsOperationTrait;

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // User entity.
    if (isset($match)) {
      $query->condition('name', $match, $match_operator);
    }

    // Only show un-blocked users.
    $query->condition('status', 1);

    // Remove anonymous user:
    $query->condition($this->entityType->getKey('id'), '0', '<>');

    // Event access rules.
    $condition_count = 0;
    $rules = $this->eventMeta->getRules('rng_event.register');
    foreach ($rules as $rule) {
      if ($this->ruleGrantsOperation($rule, 'create')) {
        foreach ($rule->getConditions() as $condition_storage) {
          // Do not use condition if it cannot alter query.
          if (($condition = $condition_storage->createInstance()) instanceof RNGConditionInterface) {
            $condition_count++;
            $condition->alterQuery($query);
          }
        }
      }
    }

    // Cancel the query if there are no conditions.
    if (!$condition_count) {
      $query->condition($this->entityType->getKey('id'), NULL, 'IS NULL');
      return $query;
    }

    // Apply proxy registration permissions for the current user.
    $proxy_count = 0;
    // if user can register `authenticated` users:
    $all_users = FALSE;
    $group = $query->orConditionGroup();

    // Self.
    if ($this->currentUser->hasPermission('rng register self')) {
      $proxy_count++;
      $group->condition($this->entityType->getKey('id'), $this->currentUser->id(), '=');
    }

    foreach (user_roles(TRUE) as $role) {
      $role_id = $role->id();
      if ($this->currentUser->hasPermission("rng register role $role_id")) {
        if ($role_id == 'authenticated') {
          $all_users = TRUE;
          break;
        }
        else {
          $proxy_count++;
          $group->condition('roles', $role_id, '=');
        }
      }
    }

    if ($all_users) {
      // Do not add any conditions.
    }
    elseif ($proxy_count) {
      $query->condition($group);
    }
    else {
      // cancel the query:
      $query->condition($this->entityType->getKey('id'), NULL, 'IS NULL');
    }

    return $query;
  }

}
