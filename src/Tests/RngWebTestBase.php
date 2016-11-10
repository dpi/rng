<?php

namespace Drupal\rng\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\rng\Entity\EventType;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\rng\Entity\Registration;

/**
 * Sets up page and article content types.
 */
abstract class RngWebTestBase extends WebTestBase {

  use RngTestTrait {
    RngTestTrait::createEventType as traitCreateEventType;
  }

  public static $modules = array('rng');

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->eventManager = $this->container->get('rng.event_manager');
  }

  /**
   * {@inheritdoc}
   */
  function createEventType($entity_type_id, $bundle, $values = []) {
    $event_type = $this->traitCreateEventType($entity_type_id, $bundle, $values);
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
