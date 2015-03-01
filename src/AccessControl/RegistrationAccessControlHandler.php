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
   * @param RegistrationInterface $entity
   *   A registration entity.
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    // @todo, needs customisation at event level.
    //        currently lets user do whatever he likes to registration as long
    //        as he is a registrant.
    $account = $this->prepareUser($account);

    if (!$account->isAnonymous() && in_array($operation, array('view', 'update', 'delete'))) {
      $user = entity_load('user', $account->id());
      if ($entity->hasIdentity($user)) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   *
   * @param RegistrationTypeInterface $entity_bundle
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

    if (empty($event->{RNG_FIELD_EVENT_TYPE_ALLOW_DUPLICATE_REGISTRANTS}->value)) {
      $registration_count = \Drupal::entityQuery('registrant')
        ->condition('identity__target_type', 'user', '=')
        ->condition('identity__target_id', $account->id(), '=')
        ->condition('registration.entity.event__target_type', $event->getEntityTypeId(), '=')
        ->condition('registration.entity.event__target_id', $event->id(), '=')
        ->count()
        ->execute();
      if ($registration_count) {
        return AccessResult::neutral();
      }
    }

    return AccessResult::allowed();
  }

}
