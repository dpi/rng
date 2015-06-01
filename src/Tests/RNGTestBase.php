<?php

/**
 * @file
 * Definition of Drupal\rng\Tests\RNGTestBase.
 */

namespace Drupal\rng\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Sets up page and article content types.
 */
abstract class RNGTestBase extends WebTestBase {

  public static $modules = array('rng');

  /**
   * Create and save a registration type entity.
   *
   * @return \Drupal\rng\Entity\RegistrationType
   *   A registration type entity
   */
  function createRegistrationType() {
    $registration_type = \Drupal::entityManager()->getStorage('registration_type')->create([
      'id' => 'registration_type_a',
      'label' => 'Registration Type A',
      'description' => 'Description for registration type a',
    ]);
    $registration_type->save();
    return $registration_type;
  }

}
