<?php

namespace Drupal\rng;

/**
 * The registrant entity factory interface.
 */
interface RegistrantFactoryInterface {

  /**
   * Create a registrant entity based on available contexts.
   *
   * @param array $context
   *   An array of contexts, including:
   *    - event (required): An event entity.
   *    - identity_entity_type: Entity type ID of the identity.
   *    - identity_bundle: Bundle of the identity.
   *    - identity: A identity entity.
   *
   * @return \Drupal\rng\RegistrantInterface
   *   A registrant entity.
   *
   * @throws \InvalidArgumentException
   *   If missing required context.
   * @throws \Exception
   *   Miscellaneous errors, including missing configuration on event type.
   */
  public function createRegistrant(array $context);

}
