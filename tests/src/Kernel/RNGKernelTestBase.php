<?php

/**
 * @file
 * Contains \Drupal\Tests\rng\Kernel\RNGKernelTestBase.
 */

namespace Drupal\Tests\rng\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rng\Tests\RNGTestTrait;

/**
 * Base class for RNG unit tests.
 */
abstract class RNGKernelTestBase extends KernelTestBase {

  use RNGTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['rng', 'user', 'field', 'dynamic_entity_reference', 'unlimited_number', 'courier', 'text'];

}
