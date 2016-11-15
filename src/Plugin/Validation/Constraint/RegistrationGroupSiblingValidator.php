<?php

namespace Drupal\rng\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates registration groups dependencies and conflicts.
 */
class RegistrationGroupSiblingValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var $items \Drupal\Core\Field\EntityReferenceFieldItemList */
    /** @var \Drupal\rng\GroupInterface $groups */
    $groups = $items->referencedEntities();
    foreach ($groups as $k => $group) {
      $t_args = ['@group' => $group->label()];
      $siblings = $groups;
      unset($siblings[$k]);
      foreach ($siblings as $sibling) {
        if (in_array($sibling, $group->getConflictingGroups())) {
          $t_args['@group_conflict'] = $sibling->label();
          $this->context->addViolation($constraint->conflict, $t_args);
        }
      }

      unset($t_args['@group_conflict']);
      foreach ($group->getDependentGroups() as $group_dependent) {
        if (!in_array($group_dependent, $groups)) {
          $t_args['@group_dependent'] = $group_dependent->label();
          $this->context->addViolation($constraint->missingDependency, $t_args);
        }
      }
    }
  }

}
