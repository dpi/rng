<?php

namespace Drupal\rng\Event;

use Drupal\rng\RegistrationInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Registration event.
 *
 * @see \Drupal\rng\Event\RegistrationEvents
 */
class RegistrationEvent extends Event {

  /**
   * The registration.
   *
   * @var \Drupal\rng\RegistrationInterface
   */
  protected $registration;

  /**
   * RegistrationEvent constructor.
   *
   * @param \Drupal\rng\RegistrationInterface $registration
   *   The registration.
   */
  public function __construct(RegistrationInterface $registration) {
    $this->registration = $registration;
  }

  /**
   * Get the registration.
   *
   * @return \Drupal\rng\RegistrationInterface
   *   The registration.
   */
  public function getRegistration() {
    return $this->registration;
  }

}
