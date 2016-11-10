<?php

namespace Drupal\rng\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\rng\Entity\Group;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller for Registration Groups.
 */
class GroupController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Provides a group add form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $rng_event
   *   The RNG event.
   *
   * @return array
   *   A registration form.
   */
  public function GroupAdd(EntityInterface $rng_event) {
    $group = Group::create()
      ->setEvent($rng_event);
    return $this->entityFormBuilder()
      ->getForm($group, 'add', [$rng_event]);
  }

  /**
   * Provides a list of registration groups for an event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $rng_event
   *   The RNG event.
   *
   * @return array
   *   A render array.
   */
  public function listing(EntityInterface $rng_event) {
    return $this->entityTypeManager()
      ->getListBuilder('registration_group')
      ->render($rng_event);
  }

}
