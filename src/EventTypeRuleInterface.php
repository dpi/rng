<?php

namespace Drupal\rng;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface EventTypeRuleInterface extends ConfigEntityInterface {

  /**
   * Get the entity type for the event type rule.
   *
   * @return string
   */
  public function getEventEntityTypeId();

  /**
   * Get the bundle for the event type rule.
   *
   * @return string
   */
  public function getEventBundle();

  /**
   * Get the unique machine name for the event type rule.
   *
   * @return string
   */
  public function getMachineName();

  /**
   * Get the trigger for the event type rule.
   *
   * @return string
   */
  public function getTrigger();

  /**
   * Get all condition plugin configurations.
   *
   * @return array
   */
  public function getConditions();

  /**
   * Get all action plugin configurations.
   *
   * @return array
   */
  public function getActions();

  /**
   * Get a condition configuration.
   *
   * @param $name
   *   A condition plugin instance ID.
   *
   * @return array
   */
  public function getCondition($name);

  /**
   * Get a action configuration.
   *
   * @param $name
   *   A action plugin instance ID.
   *
   * @return array
   */
  public function getAction($name);

  /**
   * Set a condition configuration.
   *
   * @param $name
   *   A condition plugin instance ID.
   * @param $configuration
   *   The condition plugin configuration
   *
   * @return $this
   *   The event type rule.
   */
  public function setCondition($name, $configuration);

  /**
   * Set an action configuration.
   *
   * @param $name
   *   A action plugin instance ID.
   * @param $configuration
   *   The action plugin configuration
   *
   * @return $this
   *   The event type rule.
   */
  public function setAction($name, $configuration);

  /**
   * Remove a condition configuration.
   *
   * @param $name
   *   A condition plugin instance ID.
   *
   * @return $this
   *   The event type rule.
   */
  public function removeCondition($name);

  /**
   * Remove an action configuration.
   *
   * @param $name
   *   A action plugin instance ID.
   *
   * @return $this
   *   The event type rule.
   */
  public function removeAction($name);

}
