<?php

/**
 * @file
 * Contains \Drupal\rng\AccessControl\RegistrationAccessControlHandler.
 */

namespace Drupal\rng\AccessControl;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\rng\RuleGrantsOperationTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessException;
use Drupal\rng\Exception\InvalidEventException;
use Drupal\user\Entity\User;

/**
 * Access controller for the registration entity.
 */
class RegistrationAccessControlHandler extends EntityAccessControlHandler {

  use RuleGrantsOperationTrait;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type) {
    parent::__construct($entity_type);
    $this->eventManager = \Drupal::service('rng.event_manager');
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\rng\RegistrationInterface $entity
   *   A registration entity.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $account = $this->prepareUser($account);

    if (!$account->isAnonymous() && in_array($operation, array('view', 'update', 'delete'))) {
      if ($account->hasPermission('administer rng')) {
        return AccessResult::allowed()->cachePerPermissions();
      }
      $event = $entity->getEvent();

      // Event access rules.
      $user = User::load($account->id());
      $context_values = [
        'event' => $event,
        'registration' => $entity,
        'user' => $user,
      // Replace with rng_identity.
      ];

      $rules = $this->eventManager->getMeta($event)->getRules('rng_event.register', TRUE);
      foreach ($rules as $rule) {
        if ($this->ruleGrantsOperation($rule, $operation) && $rule->evaluateConditions($context_values)) {
          return AccessResult::allowed()->cachePerUser();
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

    try {
      $event_meta = $this->eventManager->getMeta($context['event']);

      // $entity_bundle is omitted for registration type list at
      // $event_path/register
      if ($entity_bundle && !$event_meta->registrationTypeIsValid($entity_bundle)) {
        return AccessResult::neutral();
      }
      // There are no registration types configured.
      elseif (!$event_meta->getRegistrationTypeIds()) {
        return AccessResult::neutral();
      }

      if (!$event_meta->isAcceptingRegistrations()) {
        return AccessResult::neutral();
      }

      if ($event_meta->remainingCapacity() == 0) {
        return AccessResult::neutral();
      }

      if (!$event_meta->countProxyIdentities()) {
        return AccessResult::neutral();
      }

      return AccessResult::allowed();
    }
    catch (InvalidEventException $e) {
      return AccessResult::neutral();
    }
  }

}
