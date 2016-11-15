<?php

namespace Drupal\rng;

/**
 * The RNG Configuration service interface.
 */
interface RngConfigurationInterface {

  /**
   * Get valid identity entity types.
   *
   * @return string[]
   *   Array of entity types IDs.
   */
  public function getIdentityTypes();

}
