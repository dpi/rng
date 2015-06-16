<?php

/**
 * @file
 * Definition of Drupal\rng\Tests\RNGTestBase.
 */

namespace Drupal\rng\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\rng\Entity\EventType;

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
      'mirror_update_permission' => 'update',
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
    /*
    $entity_type_id = $entity_type->getEntityType()->getBundleOf();
    $entity = \Drupal::entityManager()->getStorage($entity_type_id)->create([
      'type' => $entity_type->id(),
    ] + $settings);
    //$entity->save();*/
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
    $registration_type = \Drupal::entityManager()->getStorage('registration_type')->create([
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
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   An event entity.
   * @param string $registration_type_id
   *   A registration type ID.
   *
   * @return \Drupal\rng\Entity\Registration
   *   A saved registration entity.
   *
   */
  function createRegistration(EntityInterface $event, $registration_type_id) {
    $registration = \Drupal::entityManager()
      ->getStorage('registration')
      ->create(array(
        'type' => $registration_type_id,
      ));
    $registration->setEvent($event);
    $registration->save();
    return $registration;
  }

}
