<?php

namespace Drupal\rng\Form;

use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\courier\Entity\TemplateCollection;
use Drupal\rng\EventManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\rng\Plugin\Condition\CurrentTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\rng\RuleInterface;

/**
 * Creates message list form.
 */
class MessageListForm extends FormBase {

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new message list form.
   *
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(RedirectDestinationInterface $redirect_destination, EventManagerInterface $event_manager) {
    $this->redirectDestination = $redirect_destination;
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('redirect.destination'),
      $container->get('rng.event_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rng_message_list';
  }

  /**
   * Get a list of rules.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   An event entity.
   *
   * @return \Drupal\rng\RuleInterface[]
   *   An array of rng_rule entities keyed by rule ID.
   */
  protected function getCommunicationRules(EntityInterface $event) {
    // List of communication related action plugin ids.
    $communication_actions = ['rng_courier_message'];
    $rules = [];

    $rules_all = $this->eventManager
      ->getMeta($event)
      ->getRules(NULL, FALSE, NULL);
    foreach ($rules_all as $rid => $rule) {
      foreach ($rule->getActions() as $action) {
        $action_id = $action->getPluginId();
        if (in_array($action_id, $communication_actions)) {
          $rules[$rid] = $rule;
          continue 2;
        }
      }
    }

    return $rules;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $rng_event = NULL) {
    $form['#rng_event'] = $rng_event;

    // @todo: move trigger definitions to a discovery service.
    $rng_triggers = [
      'entity:registration:new' => $this->t('Registration creation'),
      'entity:registration:update' => $this->t('Registration updated'),
      'rng:custom:date' => $this->t('Send on a date'),
    ];

    // Actions.
    $form['actions'] = [
      '#type' => 'details',
      '#title' => $this->t('Operations'),
      '#attributes' => [
        'class' => ['container-inline'],
      ],
      '#open' => TRUE,
    ];
    $form['actions']['operation'] = [
      '#title' => $this->t('With selection'),
      '#type' => 'select',
      '#options' => [
        'enable' => $this->t('Enable messages'),
        'disable' => $this->t('Disable messages'),
        'delete' => $this->t('Delete messages'),
      ],
      '#empty_option' => $this->t(' - Select - '),
      '#button_type' => 'primary',
    ];
    $form['actions']['apply'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
      '#button_type' => 'primary',
    ];

    // List items.
    $form['list'] = [
      '#type' => 'courier_template_collection_list',
      '#checkboxes' => TRUE,
      '#items' => [],
    ];

    foreach ($this->getCommunicationRules($form['#rng_event']) as $rid => $rule) {
      $trigger_id = $rule->getTriggerID();
      if ($template_collection = $this->getTemplateCollectionForRule($rule)) {
        // Add description for date conditions.
        $description = NULL;
        if ($component = $this->getDateCondition($rule)) {
          $condition = $component->createInstance();
          $description = $condition->getDateFormatted();
        }

        $form['list']['#items'][$rule->id()] = [
          '#title' => $this->t('@label (@status)', [
            '@label' => isset($rng_triggers[$trigger_id]) ? $rng_triggers[$trigger_id] : $trigger_id,
            '@status' => $rule->isActive() ? $this->t('active') : $this->t('disabled'),
          ]),
          '#description' => $description,
          '#template_collection' => $template_collection,
          '#operations' => $this->getOperations($rule),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $message = NULL;
    $operation = $form_state->getValue(['operation']);

    // Checkbox is checked, keyed by rule ID.
    $checkbox = $form_state->getValue(['list', 'checkboxes']);

    // A list of checked rules.
    $rules = [];
    foreach ($this->getCommunicationRules($form['#rng_event']) as $rule) {
      // Checkbox is checked.
      if ($checkbox[$rule->id()]) {
        $rules[] = $rule;
      }
    }

    /** @var RuleInterface $rule */
    foreach ($rules as $rule) {
      if (in_array($operation, ['enable', 'disable'])) {
        $operation_active = $operation == 'enable';
        if ($rule->isActive() != $operation_active) {
          $rule
            ->setIsActive($operation_active)
            ->save();
        }
        $message = ($operation == 'enable') ? $this->t('Messages enabled.') : $this->t('Messages disabled.');
      }
      elseif ($operation == 'delete') {
        $rule->delete();
        $message = $this->t('Messages deleted');
      }
    }

    drupal_set_message($message ? $message : $this->t('No action performed.'));
  }

  /**
   * Gets the template collection from an action on the rule.
   *
   * @param \Drupal\rng\RuleInterface $rule
   *   The rule.
   *
   * @return \Drupal\courier\TemplateCollectionInterface|NULL
   *   A template collection entity, or NULL if no template collection is
   *   associated.
   */
  protected function getTemplateCollectionForRule(RuleInterface $rule) {
    foreach ($rule->getActions() as $action) {
      $conf = $action->getConfiguration();
      $id = $conf['template_collection'];
      if ($id && $template_collection = TemplateCollection::load($id)) {
        return $template_collection;
      }
    }
    return NULL;
  }

  /**
   * Gets the condition containing a date instance.
   *
   * @param \Drupal\rng\RuleInterface $rule
   *   The rule.
   *
   * @return \Drupal\rng\RuleComponentInterface|NULL
   *   A rule component entity, or NULL if no date condition is associated.
   */
  protected function getDateCondition(RuleInterface $rule) {
    foreach ($rule->getConditions() as $component) {
      $condition = $component->createInstance();
      if ($condition instanceof CurrentTime) {
        return $component;
      }
    }
    return NULL;
  }

  /**
   * Gets operations for a rule.
   *
   * @param \Drupal\rng\RuleInterface $rule
   *   The rule.
   *
   * @return array
   *   An array of links suitable for an 'operations' element.
   */
  protected function getOperations(RuleInterface $rule) {
    $links = [];
    $destination = $this->redirectDestination->getAsArray();

    if ($component = $this->getDateCondition($rule)) {
      if ($component->access('edit')) {
        $links['edit-date'] = [
          'title' => $this->t('Edit date'),
          'url' => $component->urlInfo('edit-form'),
          'query' => $destination,
        ];
      }
    }

    if ($rule->access('delete')) {
      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => $rule->urlInfo('delete-form'),
        'query' => $destination,
      ];
    }

    return $links;
  }

}
