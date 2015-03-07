<?php

/**
 * @file
 * Contains \Drupal\rng\RNGConditionInterface.
 */

namespace Drupal\rng;

interface RNGConditionInterface {
  /**
   * Modify a query with condition configuration.
   *
   * This does not rely on any contexts, only valid configuration.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query object.
   */
  public function alterQuery(&$query);
}