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
   * {@inheritdoc}
   *
   * @var array
   */
  public static $modules = ['rng', 'user', 'field', 'dynamic_entity_reference', 'unlimited_number', 'courier', 'text'];

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->eventManager = $this->container->get('rng.event_manager');
  }

}
