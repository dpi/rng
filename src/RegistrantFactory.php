<?php

namespace Drupal\rng;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * The registrant entity factory.
 */
class RegistrantFactory implements RegistrantFactoryInterface {

  /**
   * Storage for registrant entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $registrantStorage;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new RegistrantFactory object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventManagerInterface $event_manager) {
    $this->registrantStorage = $entity_type_manager
      ->getStorage('registrant');
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createRegistrant(array $context) {
    if (!isset($context['event']) || (!$context['event'] instanceof EntityInterface)) {
      throw new \InvalidArgumentException('Registrant factory missing event context.');
    }

    $event = $context['event'];
    $event_meta = $this->eventManager->getMeta($event);
    $event_type = $event_meta->getEventType();

    $values = [
      'type' => $event_type->getDefaultRegistrantType(),
    ];

    $registrant = $this->registrantStorage->create($values);

    return $registrant;
  }

}
