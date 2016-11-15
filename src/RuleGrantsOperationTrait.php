<?php

namespace Drupal\rng;

/**
 * Defines a trait for working with 'registration operation' actions.
 */
trait RuleGrantsOperationTrait {

  /**
   * Checks if any operation actions on a rule grant $operation access.
   *
   * This does not evaluate conditions.
   *
   * @param \Drupal\rng\RuleInterface $rule
   *   A rule entity.
   * @param string $operation
   *   A registration operation.
   *
   * @return bool
   *   Whether $operation is granted by the actions.
   */
  protected function ruleGrantsOperation(RuleInterface $rule, $operation) {
    $actions = $rule->getActions();
    $operations_actions = array_filter($actions, function ($action) use ($actions, $operation) {
      if ($action->getPluginId() == 'registration_operations') {
        $config = $action->getConfiguration();
        return !empty($config['operations'][$operation]);
      }
      return FALSE;
    });
    return (boolean) count($operations_actions);
  }

}
