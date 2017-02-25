<?php

namespace Drupal\rng;

/**
 * Provides an interface for RNG entity model service.
 */
interface RngEntityModelInterface {

  /**
   * Get entity operation records for relevant RNG entities during this request.
   *
   * @return \Drupal\rng\RngOperationRecord[]
   *   An array of operation records.
   */
  public function getOperationRecords();

}
