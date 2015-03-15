<?php

/**
 * @file
 * Contains \Drupal\rng\AccessControl\RegistrationAccessControlHandler
 */

namespace Drupal\rng\AccessControl;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\rng\RuleInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessException;

/**
 * Access controller for the registration entity.
 */
class RegistrationAccessControlHandler extends EntityAccessControlHandler {
  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  public function __construct(EntityTypeInterface $entity_type) {
    parent::__construct($entity_type);
    $this->eventManager = \Drupal::service('rng.event_manager');
  }

  /**
   * Checks if any operation actions on a rule grant $operation access.
   *
   * This does not evaluate conditions.
   *
   * @param \Drupal\rng\RuleInterface $rule
   *   A rule entity.
   * @param string $operation
   *   A registration operation.
   *
   * @return bool
   *   Whether $operation is granted by the actions.
   */
  protected function ruleGrantsOperation(RuleInterface $rule, $operation) {
    $actions = $rule->getActions();
    $operations_actions = array_filter($actions, function ($action) use ($actions, $operation) {
      if ($action->getPluginId() == 'registration_operations') {
        $config = $action->getConfiguration();
        return !empty($config['operations'][$operation]);
      }
      return FALSE;
    });
    return (boolean)count($operations_actions);
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\rng\RegistrationInterface $entity
   *   A registration entity.
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    $account = $this->prepareUser($account);

    if (!$account->isAnonymous() && in_array($operation, array('view', 'update', 'delete'))) {
      if ($account->hasPermission('administer rng')) {
        return AccessResult::allowed()->cachePerRole();
      }
      $event = $entity->getEvent();

      // Event access rules.
      $user = entity_load('user', $account->id());
      $context_values = [
        'rng:event' => $event,
        'rng:identity' => $user,
        'entity:registration' => $entity,
        'entity:user' => $user,
      ];

      $rules = $this->eventManager->getMeta($event)->getRules();
      foreach($rules as $rule) {
        if ($this->ruleGrantsOperation($rule, $operation) && $rule->evaluateConditions($context_values)) {
          return AccessResult::allowed();
        }
      }
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\rng\RegistrationTypeInterface|NULL $entity_bundle
   *   A registration type. Or NULL if it is a registration type listing.
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = array(), $return_as_object = FALSE) {
    if (!isset($context['event'])) {
      throw new AccessException('Requires event context.');
    }

    $account = $this->prepareUser($account);
    if ($account->isAnonymous()) {
      return AccessResult::neutral();
    }

    $event_meta = $this->eventManager->getMeta($context['event']);

    // $entity_bundle is omitted for registration type list at
    // $event_path/register
    if ($entity_bundle && !$event_meta->registrationTypeIsValid($entity_bundle)) {
      return AccessResult::neutral();
    }
    // There are no registration types configured.
    else if (!$event_meta->getRegistrationTypeIds()) {
      return AccessResult::neutral();
    }

    if (!$event_meta->isAcceptingRegistrations()) {
      return AccessResult::neutral();
    }

    if ($event_meta->remainingCapacity() == 0) {
      return AccessResult::neutral();
    }

    if (!$event_meta->duplicateRegistrantsAllowed() && !$event_meta->countProxyIdentities()) {
      return AccessResult::neutral();
    }

    return AccessResult::allowed();
  }

}
