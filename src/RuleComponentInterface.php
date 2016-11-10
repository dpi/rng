<?php

namespace Drupal\rng;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for rule component entities.
 */
interface RuleComponentInterface extends ContentEntityInterface {

  /**
   * Gets the rule entity.
   *
   * @return \Drupal\rng\Entity\Rule|NULL
   *   The rule entity. Or NULL if it does not exist.
   */
  public function getRule();

  /**
   * Sets the rule for the component.
   *
   * @return \Drupal\rng\RuleComponentInterface
   *   Return this object for chaining.
   */
  public function setRule(RuleInterface $rule);

  /**
   * Gets the component type.
   *
   * @return string
   *   The component type: 'action' or 'condition'.
   */
  public function getType();

  /**
   * Sets the component type.
   *
   * @param string $type
   *   The type of component: 'action' or 'condition'.
   *
   * @return \Drupal\rng\RuleComponentInterface
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
   * @return \Drupal\rng\RuleComponentInterface
   *   Return this object for chaining.
   */
  public function setPluginId($plugin_id);

  /**
   * Gets the configuration for the component.
   *
   * @return array
   *   Configuration for the component.
   */
  public function getConfiguration();

  /**
   * Sets the plugin configuration.
   *
   * @param array $configuration
   *   Mixed configuration
   *
   * @return \Drupal\rng\RuleComponentInterface
   *   Return this object for chaining.
   */
  public function setConfiguration(array $configuration);

  /**
   * Gets the configuration for the component.
   *
   * This should only be used if the caller does not have access to dependency
   * injection.
   *
   * @todo: change @return when condition and action plugins have a better
   * @todo: common class.
   *
   * @return \Drupal\Core\Condition\ConditionPluginBase|\Drupal\Core\Action\ConfigurableActionBase|NULL
   *   A condition or action plugin. Or NULL if the plugin does not exist.
   *
   * @throws \Exception
   *   If the plugin type is invalid.
   */
  public function createInstance();

  /**
   * Execute the component.
   *
   * @param array $context
   *   Context of execution.
   */
  public function execute(array $context);

}
