<?php

namespace Drupal\rng\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rng\RngEntityModelInterface;
use Drupal\rng\EventManagerInterface;

/**
 * Runs tasks for RNG related to the request.
 */
class RngRequestSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The RNG entity model manager.
   *
   * @var \Drupal\rng\RngEntityModelInterface
   */
  protected $rngEntityModel;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new RngRequestSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\rng\RngEntityModelInterface
   *   The RNG entity model manager.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RngEntityModelInterface $rng_entity_model,  EventManagerInterface $event_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->rngEntityModel = $rng_entity_model;
    $this->eventManager = $event_manager;
  }

  /**
   * Run RNG rules for entity operations which occurred during this request.
   *
   * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   The event to process.
   */
  public function onKernelTerminate(PostResponseEvent $event) {
    $operation_records = $this->rngEntityModel->getOperationRecords();
    foreach ($operation_records as $operation_record) {
      if ($operation_record->getEntityTypeId() == 'registration') {
        /** @var \Drupal\rng\RegistrationInterface $registration */
        $registration = $this->entityTypeManager
          ->getStorage('registration')
          ->load($operation_record->getEntityId());
        if (!$registration) {
          // Registration no longer exists, need full object to act on.
          // @todo: if entity is about to be deleted, then all existing
          // operation records should be processed in preDelete.
          continue;
        }

        switch ($operation_record->getOperation()) {
          case 'insert':
            $trigger_id = 'entity:registration:new';
            break;
          case 'update':
            $trigger_id = 'entity:registration:update';
            break;
        }

        if (isset($trigger_id)) {
          $event_meta = $this->eventManager->getMeta($registration->getEvent());
          $event_meta->trigger($trigger_id, ['registrations' => [$registration]]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Should go before other subscribers start to write their caches. Notably
    // before \Drupal\Core\EventSubscriber\KernelDestructionSubscriber to
    // prevent instantiation of destructed services.
    $events[KernelEvents::TERMINATE][] = ['onKernelTerminate', 300];
    return $events;
  }

}
