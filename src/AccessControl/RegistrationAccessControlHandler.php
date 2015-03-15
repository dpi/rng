<?php

/**
 * @file
 * Contains \Drupal\rng\AccessControl\RegistrationAccessControlHandler
 */

namespace Drupal\rng\AccessControl;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityTypeInterface;
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
        $actions = $rule->getActions();
        $operations_actions = array_filter($actions, function ($action) use ($actions, $operation) {
          if ($action->getPluginId() == 'registration_operations') {
            $config = $action->getConfiguration();
            return !empty($config['operations'][$operation]);
          }
          return FALSE;
        });

        // If there are at least one registration_operations action granting
        // $operation.
        if ($action = array_shift($operations_actions)) {
          $success = 0;
          $conditions = $rule->getConditions();
          foreach ($conditions as $condition_storage) {
            $condition = $condition_storage->createInstance();

            foreach ($condition->getContextDefinitions() as $name => $context) {
              $data_type = $context->getDataType();
              if (isset($context_values[$data_type])) {
                $condition->setContextValue($name, $context_values[$data_type]);
              }
              else if ($context->isRequired()) {
                break 2;
              }
            }

            if ($condition->evaluate()) {
              $success++;
            }
            else {
              break;
            }
          }

          // All conditions must evaluate true.
          if (count($conditions) && count($conditions) == $success) {
            return AccessResult::allowed();
          }
        }
      }
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\rng\RegistrationTypeInterface $entity_bundle
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
