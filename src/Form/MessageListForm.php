<?php

/**
 * @file
 * Contains \Drupal\rng\Form\MessageListForm.
 */

namespace Drupal\rng\Form;

use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\rng\EventManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\rng\Plugin\Condition\CurrentTime;

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
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RouteMatchInterface $route_match = NULL, $event = NULL) {
    $form['status']['#tree'] = TRUE;

    $header = [
      $this->t('Trigger'),
      $this->t('Date'),
      [
        'data' => $this->t('Enabled'),
        'class' => ['checkbox'],
      ],
      $this->t('Operations'),
    ];
    $form['action_list'] = [
      '#type' => 'table',
      '#header' => $header,
      '#title' => $this->t('Messages'),
      '#empty' => $this->t('No messages found for this event.'),
    ];

    $rng_triggers = [
      'entity:registration:new' => $this->t('When registrations are created.'),
      'entity:registration:update' => $this->t('When registrations are updated.'),
      'rng:custom:date' => $this->t('Current date is after a date.'),
    ];


    $destination = $this->redirectDestination->getAsArray();
    $form['#rng_event'] = $route_match->getParameter($event);
    $rules = $this->eventManager
      ->getMeta($form['#rng_event'])
      ->getRules(NULL, FALSE, NULL);

    // list of communication related action plugin ids.
    $communication_actions = ['rng_courier_message'];
    foreach ($rules as $rid => $rule) {
      foreach ($rule->getActions() as $action) {
        $row = [];
        $links = [];
        $action_id = $action->getPluginId();
        if (in_array($action_id, $communication_actions)) {
          // @todo: move trigger definitions to a discovery service.
          $trigger_id = $rule->getTriggerID();
          $row['trigger']['#markup'] = isset($rng_triggers[$trigger_id]) ? $rng_triggers[$trigger_id] : $trigger_id;

          $row['date']['#markup'] = $this->t('N/A');
          foreach ($rule->getConditions() as $component) {
            $condition = $component->createInstance();
            if ($condition instanceof CurrentTime) {
              $row['date']['#markup'] = $condition->getDateFormatted();
              if ($component->access('edit')) {
                $links['edit-date'] = [
                  'title' => $this->t('Edit date'),
                  'url' => $component->urlInfo('edit-form'),
                  'query' => $destination,
                ];
              }
            }
          }

          $row['status'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Active'),
            '#title_display' => 'invisible',
            '#default_value' => (bool) $rule->isActive(),
            '#wrapper_attributes' => [
              'class' => [
                'checkbox',
              ],
            ],
          ];

          if ($action->access('edit')) {
            $links['edit-templates'] = [
              'title' => $this->t('Edit templates'),
              'url' => $action->urlInfo('edit-form'),
              'query' => $destination,
            ];
          }
          if ($rule->access('delete')) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => $rule->urlInfo('delete-form'),
              'query' => $destination,
            ];
          }

          $row['operations']['data'] = [
            '#type' => 'operations',
            '#links' => $links,
          ];
        }
        else {
          continue;
        }

        $form['action_list'][$rid] = $row;
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_rules = $form_state->getValue('action_list');
    $rules = $this->eventManager
      ->getMeta($form['#rng_event'])
      ->getRules(NULL, FALSE, NULL);

    foreach ($rules as $rid => $rule) {
      $enabled = !empty($form_rules[$rid]['status']);
      if ($rule->isActive() != $enabled) {
        $rule
          ->setIsActive($enabled)
          ->save();
      }
    }
  }

}
