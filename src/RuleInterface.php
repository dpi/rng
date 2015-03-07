<?php

/**
 * @file
 * Contains \Drupal\rng\RuleInterface.
 */

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
   * Get actions for the rule.
   *
   * @return ActionInterface[]
   *   An array of action entities.
   */
  public function getActions();

  /**
   * Get conditions for the rule.
   *
   * @return ActionInterface[]
   *   An array of action entities.
   */
  public function getConditions();
}