<?php

/**
 * @file
 * Definition of Drupal\rng\Tests\RNGTestBase.
 */

namespace Drupal\rng\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\rng\Entity\EventType;
use Drupal\rng\Entity\RegistrationType;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\rng\Entity\Registration;

/**
 * Sets up page and article content types.
 */
abstract class RNGTestBase extends WebTestBase {

  public static $modules = array('rng');

  /**
   * Creates an event type config.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   An entity type.
   *
   * @return \Drupal\rng\EventTypeInterface
   *   An event type config.
   */
  function createEventType(ConfigEntityInterface $entity_type) {
    $event_type = EventType::create([
      'label' => 'Event Type A',
      'entity_type' => $entity_type->getEntityType()->getBundleOf(),
      'bundle' => $entity_type->id(),
      'mirror_operation_to_event_manage' => 'update',
    ]);
    $event_type->save();
    \Drupal::service('router.builder')->rebuildIfNeeded();
    return $event_type;
  }

  /**
   * Creates an event entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   An entity type.
   * @param array $settings
   *   Additional settings for the new entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An event.
   */
  function createEntity(ConfigEntityInterface $entity_type, $settings = []) {
    // @todo change to custom entity
    $entity = $this->drupalCreateNode([
      'type' => $entity_type->id(),
    ] + $settings);
    return $entity;
  }

  /**
   * Create and save a registration type entity.
   *
   * @return \Drupal\rng\Entity\RegistrationType
   *   A registration type entity
   */
  function createRegistrationType() {
    $registration_type = RegistrationType::create([
      'id' => 'registration_type_a',
      'label' => 'Registration Type A',
      'description' => 'Description for registration type a',
    ]);
    $registration_type->save();
    return $registration_type;
  }

  /**
   * Creates and saves a registration entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $event
   *   An event entity.
   * @param string $registration_type_id
   *   A registration type ID.
   *
   * @return \Drupal\rng\Entity\Registration
   *   A saved registration entity.
   */
  function createRegistration(ContentEntityInterface $event, $registration_type_id) {
    $registration = Registration::create([
      'type' => $registration_type_id,
    ])
      ->setEvent($event);
    $registration->save();
    return $registration;
  }

}
