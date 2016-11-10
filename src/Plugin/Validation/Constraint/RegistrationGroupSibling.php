<?php

namespace Drupal\rng\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures group siblings do not have conflicts or unmet requirements.
 *
 * @Constraint(
 *   id = "RegistrationGroupSibling",
 *   label = @Translation("Check a list of registration groups for conflicts or unmet requirements.", context = "Validation"),
 * )
 */
class RegistrationGroupSibling extends Constraint {

  public $conflict = 'Group conflict: @group cannot co-exist with @group_conflict.';
  public $missingDependency = 'Group dependency not met: @group requires @group_dependent.';

}
