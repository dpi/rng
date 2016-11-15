<?php

namespace Drupal\rng_debug\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller for rng_debug.
 */
class DebugController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Provides a list of rng rules for an event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $rng_event
   *   The RNG event.
   *
   * @return array
   *   A render array.
   */
  public function listing(EntityInterface $rng_event = NULL) {
    return $this->entityTypeManager()->getListBuilder('rng_rule')
      ->render($rng_event);
  }

}
