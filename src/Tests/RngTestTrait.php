<?php

namespace Drupal\rng\Tests;

use Drupal\rng\Entity\RegistrationType;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\rng\RegistrationTypeInterface;
use Drupal\rng\Entity\EventType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\rng\Entity\Registration;
use Drupal\rng\EventManagerInterface;
use Drupal\rng\Entity\EventTypeRule;

trait RngTestTrait {

  /**
   * Create and save a registration type entity.
   *
   * @return \Drupal\rng\RegistrationTypeInterface
   *   A registration type entity
   */
  protected function createRegistrationType() {
    $registration_type = RegistrationType::create([
      'id' => 'registration_type_a',
      'label' => 'Registration Type A',
      'description' => 'Description for registration type a',
    ]);
    $registration_type->save();
    return $registration_type;
  }

  /**
   * Creates an event type config.
   *
   * @param string $entity_type_id
   *   An entity type ID
   * @param string $bundle
   *   An entity type bundle.
   * @param array $values
   *   Optional values for the event type.
   *
   * @return \Drupal\rng\EventTypeInterface
   *   An event type config.
   */
  protected function createEventType($entity_type_id, $bundle, $values = []) {
    $event_type = EventType::create($values + [
      'label' => 'Event Type A',
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'mirror_operation_to_event_manage' => 'update',
    ]);
    $event_type->setIdentityTypeReference('user', 'user', TRUE);
    $event_type->setDefaultRegistrantType('registrant');
    $event_type->save();
    return $event_type;
  }

  /**
   * Create an event.
   *
   * @return \Drupal\rng\EventMetaInterface
   */
  protected function createEvent($values = []) {
    $event = EntityTest::create($values + [
        EventManagerInterface::FIELD_REGISTRATION_TYPE => $this->registrationType->id(),
        EventManagerInterface::FIELD_STATUS => TRUE,
        EventManagerInterface::FIELD_ALLOW_DUPLICATE_REGISTRANTS => 0,
      ]);
    $event->save();
    return $this->eventManager->getMeta($event);
  }

  /**
   * Create a registration and add an identity as a registrant.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   An event entity
   * @param \Drupal\rng\RegistrationTypeInterface $registration_type
   *   A registration type.
   * @param \Drupal\Core\Entity\EntityInterface[] $identities
   *   An array of identities.
   *
   * @return \Drupal\rng\RegistrationInterface
   *   A saved registration
   */
  protected function createRegistration(EntityInterface $event, RegistrationTypeInterface $registration_type, array $identities) {
    $registration = Registration::create([
      'type' => $registration_type->id(),
    ]);
    foreach ($identities as $identity) {
      $registration->addIdentity($identity);
    }
    $registration
      ->setEvent($event)
      ->save();
    return $registration;
  }

  /**
   * Create rules for an event type.
   *
   * @param array $roles
   *   An array of role ID to add access.
   * @param array $operations
   *   An array of operations. Value is boolean whether to grant, key can be
   *   any of 'create', 'view', 'update', 'delete'.
   */
  protected function createUserRoleRules($roles = [], $operations = []) {
    $rule = EventTypeRule::create([
      'trigger' => 'rng_event.register',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'machine_name' => 'user_role',
    ]);
    $rule->setCondition('role', [
      'id' => 'rng_user_role',
      'roles' => $roles,
    ]);
    $rule->setAction('registration_operations', [
      'id' => 'registration_operations',
      'configuration' => [
        'operations' => $operations,
      ],
    ]);
    $rule->save();
  }

}