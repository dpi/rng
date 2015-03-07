<?php

/**
 * @file
 * Contains \Drupal\rng\AccessControl\RegistrationAccessControlHandler
 */

namespace Drupal\rng\AccessControl;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessException;

/**
 * Access controller for the registration entity.
 */
class RegistrationAccessControlHandler extends EntityAccessControlHandler {

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

      $rule_ids = \Drupal::entityQuery('rng_rule')
        ->condition('event__target_type', $event->getEntityTypeId(), '=')
        ->condition('event__target_id', $event->id(), '=')
        ->condition('trigger_id', 'rng_event.register', '=')
        ->execute();

      /* @var $rule \Drupal\rng\RuleInterface */
      foreach(entity_load_multiple('rng_rule', $rule_ids) as $rule) {
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
    // @todo, needs customisation at event level.
    // @todo do event checks on wrapper
    // @todo cache event checks (access is executed per valid bundle)

    if (!isset($context['event'])) {
      throw new AccessException('Requires event context.');
    }

    /* @var $event EntityInterface */
    $event = $context['event'];
    $account = $this->prepareUser($account);

    if ($entity_bundle) {
      $registration_types = array_map(function ($element) {
        return $element['target_id'];
      }, $event->{RNG_FIELD_EVENT_TYPE_REGISTRATION_TYPE}->getValue());
      if (!in_array($entity_bundle->id(), $registration_types)) {
        return AccessResult::forbidden();
      };
    }

    if ($account->isAnonymous()) {
      return AccessResult::neutral();
    }

    if (empty($event->{RNG_FIELD_EVENT_TYPE_STATUS}->value)) {
      return AccessResult::neutral();
    }

    if ($event->{RNG_FIELD_EVENT_TYPE_REGISTRATION_TYPE}->isEmpty()) {
      return AccessResult::neutral();
    }

    $capacity = $event->{RNG_FIELD_EVENT_TYPE_CAPACITY}->value;
    if ($capacity != '' && is_numeric($capacity) && $capacity > -1) {
      $registration_count = \Drupal::entityQuery('registration')
        ->condition('event__target_type', $event->getEntityTypeId(), '=')
        ->condition('event__target_id', $event->id(), '=')
        ->count()
        ->execute();
      if ($registration_count >= $capacity) {
        return AccessResult::neutral();
      }
    }

    // Determine if current user has access to any un-registered identities.
    if (empty($event->{RNG_FIELD_EVENT_TYPE_ALLOW_DUPLICATE_REGISTRANTS}->value)) {
      $options = [
        'target_type' => 'user',
        'handler' => 'rng:register',
        'handler_settings' => ['event' => $event],
      ];
      $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
      if (!$handler->countReferenceableEntities()) {
        return AccessResult::neutral();
      }
    }

    return AccessResult::allowed();
  }

}
