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
   * Gets the plugin ID.
   *
   * @return string
   *   The plugin ID.
   */
  public function getPluginId();

  /**
   * Sets the plugin ID.
   *
   * @param string $plugin_id
   *   The plugin ID.
   *
   * @return ActionInterface
   *   Return this object for chaining.
   */
  public function setPluginId($plugin_id);

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
   * Gets the configuration for the action.
   *
   * This should only be used if the caller does not have access to dependency
   * injection.
   *
   * @todo: change @return when condition and action plugins have a better
   * @todo: common class.
   *
   * @return \Drupal\Core\Condition\ConditionPluginBase|\Drupal\Core\Action\ConfigurableActionBase|NULL
   *   A condition or action plugin. Or NULL if the plugin does not exist.
   */
  public function createInstance();

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