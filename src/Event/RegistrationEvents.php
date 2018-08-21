<?php

namespace Drupal\rng\Event;

/**
 * Defines events for registrations.
 *
 * @see \Drupal\rng\Event\RegistrationAccessEvent
 */
final class RegistrationEvents {

  /**
   * Name of the event when getting the access for creating a registration.
   *
   * This event allows modules to influence access during the registration
   * process.
   *
   * @Event
   *
   * @see \Drupal\rng\Event\RegistrationAccessEvent
   * @see \Drupal\rng\AccessControl\RegistrationAccessControlHandler::createAccess()
   *
   * @var string
   */
  const REGISTRATION_CREATE_ACCESS = 'rng.registration_create';

  /**
   * Name of the event fired after loading a registration.
   *
   * @Event
   *
   * @see \Drupal\rng\Event\RegistrationEvent
   */
  const REGISTRATION_LOAD = 'rng.registration.load';

  /**
   * Name of the event fired after creating a new registration.
   *
   * Fired before the registration is saved.
   *
   * @Event
   *
   * @see \Drupal\rng\Event\RegistrationEvent
   */
  const REGISTRATION_CREATE = 'rng.registration.create';

  /**
   * Name of the event fired before saving an registration.
   *
   * @Event
   *
   * @see \Drupal\rng\Event\RegistrationEvent
   */
  const REGISTRATION_PRESAVE = 'rng.registration.presave';

  /**
   * Name of the event fired after saving a new registration.
   *
   * @Event
   *
   * @see \Drupal\rng\Event\RegistrationEvent
   */
  const REGISTRATION_INSERT = 'rng.registration.insert';

  /**
   * Name of the event fired after saving an existing registration.
   *
   * @Event
   *
   * @see \Drupal\rng\Event\RegistrationEvent
   */
  const REGISTRATION_UPDATE = 'rng.registration.registration';

  /**
   * Name of the event fired before deleting an registration.
   *
   * @Event
   *
   * @see \Drupal\rng\Event\RegistrationEvent
   */
  const REGISTRATION_PREDELETE = 'rng.registration.predelete';

  /**
   * Name of the event fired after deleting an registration.
   *
   * @Event
   *
   * @see \Drupal\rng\Event\RegistrationEvent
   */
  const REGISTRATION_DELETE = 'rng.registration.delete';

}
