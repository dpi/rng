<?php

namespace Drupal\rng\AccessControl;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\rng\Entity\RegistrationType;
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

    if (in_array($operation, ['view', 'update', 'delete'])) {
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
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = array(), $return_as_object = FALSE) {
    // $entity_bundle: A registration type, or NULL if it is a registration type
    // listing.

    if (!isset($context['event'])) {
      throw new AccessException('Requires event context.');
    }

    $event = $context['event'];
    $fail = $return_as_object ? AccessResult::forbidden()
      ->addCacheableDependency($event)
      ->addCacheContexts(['rng_event', 'user']) : FALSE;

    $account = $this->prepareUser($account);

    try {
      $event_meta = $this->eventManager->getMeta($event);

      // $entity_bundle is omitted for registration type list at
      // $event_path/register
      if ($entity_bundle && ($registration_type = RegistrationType::load($entity_bundle))) {
        if (!$event_meta->registrationTypeIsValid($registration_type)) {
          return $fail;
        }
      }
      // There are no registration types configured.
      elseif (!$event_meta->getRegistrationTypeIds()) {
        return $fail;
      }

      if (!$event_meta->isAcceptingRegistrations()) {
        return $fail;
      }

      if ($event_meta->remainingCapacity() == 0) {
        return $fail;
      }

      if (!$event_meta->canRegisterProxyIdentities()) {
        return $fail;
      }

      $result = parent::createAccess($entity_bundle, $account, $context, TRUE);
      if ($result->isForbidden()) {
        return $return_as_object ? $result : $result->isAllowed();
      }

      return $return_as_object ? AccessResult::allowed()
        ->addCacheableDependency($event)
        ->addCacheContexts(['rng_event', 'user']) : TRUE;
    }
    catch (InvalidEventException $e) {
      return $fail;
    }
  }

}
