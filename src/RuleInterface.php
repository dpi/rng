<?php

namespace Drupal\rng;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for event rule entities.
 */
interface RuleInterface extends ContentEntityInterface {

  /**
   * Gets the event entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|NULL
   *   The event entity. Or NULL if it does not exist.
   */
  public function getEvent();

  /**
   * Gets the trigger ID for the rule.
   *
   * @return string
   *   The trigger ID.
   */
  public function getTriggerID();

  /**
   * Determine if the can be executed.
   *
   * @return bool
   *   Whether the rule can be executed.
   */
  public function isActive();

  /**
   * Set if the rule can be executed.
   *
   * @param bool $is_active
   *   Whether the rule can be executed.
   *
   * @return \Drupal\rng\RuleInterface
   *   Return this object for chaining.
   */
  public function setIsActive($is_active);

  /**
   * Get actions for the rule.
   *
   * @return \Drupal\rng\RuleComponentInterface[]
   *   An array of action entities.
   */
  public function getActions();

  /**
   * Get conditions for the rule.
   *
   * @return \Drupal\rng\RuleComponentInterface[]
   *   An array of action entities.
   */
  public function getConditions();

  /**
   * Add components to the rule.
   *
   * Components are not saved until the rule is saved.
   *
   * @param \Drupal\rng\RuleComponentInterface $component
   *   The rule component entity.
   *
   * @return \Drupal\rng\RuleInterface
   *   Return this object for chaining.
   */
  public function addComponent(RuleComponentInterface $component);

  /**
   * Evaluates all conditions on the rule.
   *
   * @param array $context_values
   *   Context to pass to conditions. Keyed by context name.
   *
   * @return bool
   *   Whether all conditions evaluate true.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   *   If a context value is missing for any condition.
   */
  public function evaluateConditions($context_values = []);

}
