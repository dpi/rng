<?php

namespace Drupal\rng\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rng\Event\RegistrationEvent;
use Drupal\rng\Event\RegistrationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\rng\EventManagerInterface;

/**
 * Class RegistrationWaitListSubscriber.
 */
class RegistrationWaitListSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Drupal\rng\EventManagerInterface definition.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $rngEventManager;

  /**
   * The messenger server.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * RegistrationWaitListSubscriber constructor.
   *
   * @param \Drupal\rng\EventManagerInterface $rng_event_manager
   *   The event manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EventManagerInterface $rng_event_manager, MessengerInterface $messenger) {
    $this->rngEventManager = $rng_event_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RegistrationEvents::REGISTRATION_INSERT] = ['onRegistrationInsert', -1];
    return $events;
  }

  /**
   * Notify the user if they are added to a wait list.
   *
   * @param \Drupal\rng\Event\RegistrationEvent $event
   *   The event.
   *
   * @throws \Drupal\rng\Exception\InvalidEventException
   */
  public function onRegistrationInsert(RegistrationEvent $event) {
    $meta = $this->rngEventManager->getMeta($event->getRegistration()->getEvent());
    if ($meta->allowWaitList() && $meta->remainingCapacity() < 1) {
      $this->messenger->addStatus($this->t('Registration is at its capacity. You have been added to a waiting list.'));
    }
  }

}
