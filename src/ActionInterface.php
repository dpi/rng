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
   * Gets the action ID.
   *
   * @return string
   *   The action ID.
   */
  public function getActionID();

  /**
   * Gets the configuration for the action.
   *
   * @return array
   *   Configuration for the action.
   */
  public function getConfiguration();

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