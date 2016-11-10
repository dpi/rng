<?php

namespace Drupal\rng\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\rng\EventTypeRuleInterface;

/**
 * Defines the event type entity.
 *
 * @ConfigEntityType(
 *   id = "rng_event_type_rule",
 *   label = @Translation("Event type rule"),
 *   admin_permission = "administer event types",
 *   config_prefix = "rule",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id"
 *   }
 * )
 */
class EventTypeRule extends ConfigEntityBase implements EventTypeRuleInterface {

  /**
   * The event entity type.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * The event bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * A unique machine name for this rule.
   *
   * @var string
   */
  protected $machine_name;

  /**
   * The trigger for the rule.
   *
   * @var string
   */
  protected $trigger;

  /**
   * Conditions.
   */
  protected $conditions = [];

  /**
   * Actions.
   */
  protected $actions = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->entity_type . '.' . $this->bundle . '.' . $this->machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventEntityTypeId() {
    return $this->entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName() {
    return $this->machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrigger() {
    return $this->trigger;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditions() {
    return $this->conditions;
  }

  /**
   * {@inheritdoc}
   */
  public function getActions() {
    return $this->actions;
  }

  /**
   * {@inheritdoc}
   */
  public function getCondition($name) {
    return isset($this->conditions[$name]) ? $this->conditions[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAction($name) {
    return isset($this->actions[$name]) ? $this->actions[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setCondition($name, $configuration) {
    $this->conditions[$name] = $configuration;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAction($name, $configuration) {
    $this->actions[$name] = $configuration;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeCondition($name) {
    unset($this->conditions[$name]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeAction($name) {
    unset($this->actions[$name]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    if ($event_type = EventType::load($this->getEventEntityTypeId() . '.' . $this->getEventBundle())) {
      $this->addDependency('config', $event_type->getConfigDependencyName());
    }

    return $this;
  }

}
