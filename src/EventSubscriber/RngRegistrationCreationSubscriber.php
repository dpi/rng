<?php

namespace Drupal\rng\EventSubscriber;

use Drupal\rng\Entity\RegistrationType;
use Drupal\rng\Event\RegistrationAccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\rng\EventManagerInterface;

/**
 * Class RngRegistrationCreationSubscriber.
 */
class RngRegistrationCreationSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\rng\EventManagerInterface definition.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $rngEventManager;

  /**
   * RngRegistrationCreationSubscriber constructor.
   *
   * @param \Drupal\rng\EventManagerInterface $rng_event_manager
   *   The rng event manager.
   */
  public function __construct(EventManagerInterface $rng_event_manager) {
    $this->rngEventManager = $rng_event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['rng.registration_create'][] = ['invalidEntityBundle'];
    $events['rng.registration_create'][] = ['missingRegistrationType'];
    $events['rng.registration_create'][] = ['acceptingRegistration'];
    $events['rng.registration_create'][] = ['canRegisterProxies'];
    // Setting this to a lower weight so it can potentially be easily skipped.
    $events['rng.registration_create'][] = ['remainingCapacity', -1];
    return $events;
  }

  /**
   * Determines if there is an invalid entity bundle.
   *
   * @param \Drupal\rng\Event\RegistrationAccessEvent $event
   */
  public function invalidEntityBundle(RegistrationAccessEvent $event) {
    $meta = $this->getMeta($event->getContext());
    // $entity_bundle is omitted for registration type list at
    // $event_path/register
    if ($event->getEntityBundle() && ($registration_type = RegistrationType::load($event->getEntityBundle()))) {
      if (!$meta->registrationTypeIsValid($registration_type)) {
        $event->setAccess(FALSE);
      }
    }
  }

  /**
   * Determines if there are no registration types configured.
   *
   * @param \Drupal\rng\Event\RegistrationAccessEvent $event
   */
  public function missingRegistrationType(RegistrationAccessEvent $event) {
    $meta = $this->getMeta($event->getContext());
    if (!$meta->getRegistrationTypeIds()) {
      $event->setAccess(FALSE);
    }
  }

  /**
   * Determines if there is any remaining capacity.
   *
   * @param \Drupal\rng\Event\RegistrationAccessEvent $event
   */
  public function acceptingRegistration(RegistrationAccessEvent $event) {
    $meta = $this->getMeta($event->getContext());
    if (!$meta->isAcceptingRegistrations()) {
      $event->setAccess(FALSE);
    }
  }

  /**
   * Determines if there is any remaining capacity.
   *
   * @param \Drupal\rng\Event\RegistrationAccessEvent $event
   */
  public function remainingCapacity(RegistrationAccessEvent $event) {
    $meta = $this->getMeta($event->getContext());
    if (!$meta->allowWaitList() && $meta->remainingCapacity() < 1) {
      $event->setAccess(FALSE);
    }
  }

  /**
   * Determines if user can register proxies.
   *
   * @param \Drupal\rng\Event\RegistrationAccessEvent $event
   */
  public function canRegisterProxies(RegistrationAccessEvent $event) {
    $meta = $this->getMeta($event->getContext());
    if (!$meta->canRegisterProxyIdentities()) {
      $event->setAccess(FALSE);
    }
  }

  /**
   * Get the event meta.
   *
   * @param array $context
   *
   * @return \Drupal\rng\EventMetaInterface|null
   *   The event meta or NULL.
   *
   * @throws \Drupal\rng\Exception\InvalidEventException
   */
  protected function getMeta(array $context) {
    return $this->rngEventManager->getMeta($context['event']);
  }

}
