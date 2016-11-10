<?php

namespace Drupal\rng\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Action\ActionManager;
use Drupal\Core\Condition\ConditionManager;
use Drupal\rng\EventManagerInterface;
use Drupal\rng\EventTypeRuleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Configure event settings.
 */
class EventTypeRuleComponentEdit extends FormBase {

  /**
   * The action manager service.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $actionManager;

  /**
   * The condition manager service.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * The event type rule.
   *
   * @var \Drupal\rng\EventTypeRuleInterface
   */
  var $eventTypeRule;

  /**
   * The component type. 'action' or 'condition'.
   *
   * @var string
   */
  var $componentType;

  /**
   * The component key from the event type rule.
   *
   * @var string
   */
  var $componentId;

  /**
   * The plugin instance.
   *
   * @var \Drupal\Core\Condition\ConditionInterface|\Drupal\Core\Action\ActionInterface
   */
  protected $plugin;

  /**
   * Constructs a new MessageActionForm object.
   *
   * @param \Drupal\Core\Action\ActionManager $action_manager
   *   The action manager.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition manager.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(ActionManager $action_manager, ConditionManager $condition_manager, EventManagerInterface $event_manager) {
    $this->actionManager = $action_manager;
    $this->conditionManager = $condition_manager;
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.action'),
      $container->get('plugin.manager.condition'),
      $container->get('rng.event_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rng_event_type_rule_component_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EventTypeRuleInterface $rng_event_type_rule = NULL, $component_type = NULL, $component_id = NULL) {
    $this->eventTypeRule = $rng_event_type_rule;
    $this->componentType = $component_type;
    $this->componentId = $component_id;

    if ($this->componentType == 'condition') {
      $manager = 'conditionManager';
      $components = $this->eventTypeRule->getConditions();
      $configuration = $components[$this->componentId];
      $plugin_id = $configuration['id'];
      unset($configuration['id']);
    }
    else if ($this->componentType == 'action') {
      $manager = 'actionManager';
      $components = $this->eventTypeRule->getActions();
      $plugin_id = $components[$this->componentId]['id'];
      $configuration = $components[$this->componentId]['configuration'];
    }
    else {
      return $form;
    }

    $this->plugin = $this->{$manager}->createInstance($plugin_id, $configuration);

    $form += $this->plugin->buildConfigurationForm($form, $form_state);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->plugin->submitConfigurationForm($form, $form_state);

    $configuration = $this->plugin->getConfiguration();

    if ($this->componentType == 'condition') {
      $this->eventTypeRule
        ->setCondition($this->componentId, $configuration);
    }
    else if ($this->componentType == 'action') {
      $this->eventTypeRule
        ->setAction($this->componentId, $configuration);
    }
    $this->eventTypeRule->save();

    $event_type = $this->eventManager->eventType(
      $this->eventTypeRule->getEventEntityTypeId(),
      $this->eventTypeRule->getEventBundle()
    );

    $this->eventManager->invalidateEventType($event_type);
  }

}
