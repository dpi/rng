<?php

/**
 * @file
 * Contains \Drupal\rng\ActionInterface.
 */

namespace Drupal\rng;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for event action entities.
 */
interface ActionInterface extends ContentEntityInterface {
  /**
   * Gets the rule entity.
   *
   * @return \Drupal\rng\Entity\Rule|NULL
   *   The rule entity. Or NULL if it does not exist.
   */
  public function getRule();

  /**
   * Sets the rule for the action.
   *
   * @return ActionInterface
   *   Return this object for chaining.
   */
  public function setRule(RuleInterface $rule);

  /**
   * Gets the action type.
   *
   * @return string
   *   The action type.
   */
  public function getType();

  /**
   * Sets the action type.
   *
   * @param string $type
   *   The type of action: 'action' or 'condition'.
   *
   * @return ActionInterface
   *   Return this object for chaining.
   */
  public function setType($type);

  /**
   * Gets the action ID.
   *
   * @return string
   *   The action ID.
   */
  public function getActionID();

  /**
   * Sets the action plugin ID.
   *
   * @param string $action_id
   *   The action plugin ID.
   *
   * @return ActionInterface
   *   Return this object for chaining.
   */
  public function setActionID($action_id);

  /**
   * Gets the configuration for the action.
   *
   * @return array
   *   Configuration for the action.
   */
  public function getConfiguration();

  /**
   * Sets the plugin configuration.
   *
   * @return ActionInterface
   *   Return this object for chaining.
   */
  public function setConfiguration(array $configuration);

  /**
   * Execute the action.
   *
   * @param array $context
   *   Context of execution.
   *
   * @return NULL
   */
  public function execute(array $context);
}