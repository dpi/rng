<?php

namespace Drupal\rng\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Action\ActionManager;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Cache\Cache;
use Drupal\rng\EventManagerInterface;
use Drupal\rng\RNGConditionInterface;
use Drupal\rng\Entity\RuleComponent;

/**
 * Form to edit event access.
 */
class EventAccessForm extends FormBase {

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
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The event entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $event;

  /**
   * Constructs a new EventAccessForm object.
   *
   * @param \Drupal\Core\Action\ActionManager $actionManager
   *   The action manager.
   * @param \Drupal\Core\Condition\ConditionManager $conditionManager
   *   The condition manager.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(ActionManager $actionManager, ConditionManager $conditionManager, EventManagerInterface $event_manager, RedirectDestinationInterface $redirect_destination) {
    $this->actionManager = $actionManager;
    $this->conditionManager = $conditionManager;
    $this->eventManager = $event_manager;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.action'),
      $container->get('plugin.manager.condition'),
      $container->get('rng.event_manager'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rng_event_access';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $rng_event = NULL) {
    $event = clone $rng_event;
    $this->event = $event;

    $destination = $this->redirectDestination->getAsArray();
    $event_meta = $this->eventManager->getMeta($event);
    $trigger = 'rng_event.register';

    // Allow editing operations on non default rules.
    $access_edit_operations = !$event_meta->isDefaultRules($trigger);

    $form['description'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('The following rules determine who is eligible to register or perform an operation on a registration.<br />Access is granted if all conditions for a rule evaluate as true.'),
      '#suffix' => '</p>',
    ];

    $form['table'] = [
      '#type' => 'table',
      '#header' => [],
      '#rows' => [],
      '#empty' => $this->t('No access rules.'),
    ];

    // Header.
    $form['table']['header_one'][] = [
      '#wrapper_attributes' => [
        'header' => TRUE,
        'rowspan' => 2,
      ],
      '#plain_text' => $this->t('Rule'),
    ];
    $form['table']['header_one'][] = [
      '#wrapper_attributes' => [
        'header' => TRUE,
        'rowspan' => 2,
        'colspan' => $access_edit_operations ? 2 : 1,
      ],
      '#plain_text' => $this->t('Component'),
    ];
    $form['table']['header_one'][] = [
      '#wrapper_attributes' => [
        'header' => TRUE,
        'rowspan' => 2,
      ],
      '#plain_text' => $this->t('Scope'),
    ];
    $form['table']['header_one'][] = [
      '#wrapper_attributes' => [
        'header' => TRUE,
        'rowspan' => 1,
        'colspan' => 4,
      ],
      '#plain_text' => $this->t('Operations'),
    ];

    $operations = ['create' => $this->t('Create'), 'view' => $this->t('View'), 'update' => $this->t('Update'), 'delete' => $this->t('Delete')];
    foreach ($operations as $operation) {
      $form['table']['operations'][] = [
        '#wrapper_attributes' => [
          'header' => TRUE,
          'class' => ['checkbox'],
        ],
        '#plain_text' => $operation,
      ];
    }

    $i = 0;
    $rules = $event_meta->getRules($trigger, TRUE);
    foreach ($rules as $rule) {
      $i++;
      $scope_all = FALSE;
      $supports_create = 0;
      $condition_context = [];

      // Conditions.
      $k = 0;
      $row = [];

      // no_striping does not work when using table as form element right now.
      $row['#attributes']['no_striping'] = TRUE;

      $row['rule'] = [
        '#wrapper_attributes' => [
          'header' => FALSE,
          'rowspan' => count($rule->getConditions()) + 1,
        ],
        '#plain_text' => $this->t('@row.', ['@row' => $i]),
      ];

      foreach ($rule->getConditions() as $condition_storage) {
        $k++;
        $row[] = [
          '#wrapper_attributes' => [
            'header' => TRUE,
          ],
          '#plain_text' => $this->t('Condition #@condition', ['@condition' => $k]),
        ];

        if ($access_edit_operations) {
          $row['condition_operations']['#wrapper_attributes']['header'] = TRUE;
          $row['condition_operations']['data'] = ['#type' => 'operations'];
          if ($condition_storage->access('edit')) {
            $row['condition_operations']['data']['#links']['edit'] = [
              'title' => t('Edit'),
              'url' => $condition_storage->toUrl('edit-form'),
              'query' => $destination,
            ];
          }
        }

        $condition = $condition_storage->createInstance();
        $condition_context += array_keys($condition->getContextDefinitions());
        $scope_all = (!in_array('registration', $condition_context) || in_array('event', $condition_context));
        if (isset($row['rule']['#wrapper_attributes']['rowspan']) && $scope_all) {
          $row['rule']['#wrapper_attributes']['rowspan']++;
        }

        if ($condition instanceof RNGConditionInterface) {
          $supports_create++;
        }
        $row[] = [
          '#wrapper_attributes' => [
            'colspan' => 5,
          ],
          '#markup' => $condition->summary(),
        ];

        $form['table'][] = $row;

        $row = [];
      }

      // Actions.
      foreach ($rule->getActions() as $action_storage) {
        /** @var \Drupal\rng\RuleComponentInterface $action_storage */

        $row = [];
        $row[] = [
          '#wrapper_attributes' => [
            'header' => TRUE,
            'rowspan' => $scope_all ? 2 : 1,
            'colspan' => $access_edit_operations ? 2 : 1,
          ],
          '#plain_text' => $this->t('Grants operations'),
        ];

        // Scope: warn user actions apply to all registrations.
        $row[] = [
          '#plain_text' => $scope_all ? $this->t('All registrations.') : $this->t('Single registration'),
        ];

        // Operations granted.
        $config = $action_storage->getConfiguration();
        foreach ($operations as $op => $operation) {
          $cell = [
            '#wrapper_attributes' => [
              'class' => ['checkbox'],
            ],
          ];

          if (($op == 'create' && ($supports_create != count($rule->getConditions())))) {
            $cell['#markup'] = $this->t('<em>N/A</em>');
          }
          else {
            $cell['component_id'] = [
              '#type' => 'value',
              '#value' => $action_storage->id(),
            ];

            $cell['operation'] = [
              '#type' => 'value',
              '#value' => $op,
            ];

            $cell['enabled'] = [
              '#type' => 'checkbox',
              '#title' => $this->t('Allow registrant to @operation registrations if all conditions pass on this rule.', [
                '@operation' => $op,
              ]),
              '#title_display' => 'invisible',
              '#default_value' => !empty($config['operations'][$op]),
              '#disabled' => !$access_edit_operations,
            ];
          }

          $row['operation_' . $op] = $cell;
        }

        $form['table'][] = $row;

        if ($scope_all) {
          $form['table'][][] = [
            '#wrapper_attributes' => [
              'colspan' => 5,
            ],
            '#markup' => $this->t('<strong>Warning:</strong> selecting view, update, or delete grants access to any registration on this event.'),
          ];
        }
      }
    }

    if ($access_edit_operations) {
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Save'),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $event = $this->event;
    $event_meta = $this->eventManager->getMeta($event);
    $trigger = 'rng_event.register';
    if ($event_meta->isDefaultRules($trigger)) {
      $form_state->setError($form, $this->t('This event is using default rules.'));
      return;
    }

    // Component_id => [operation => enabled?, ...]
    $component_operations = [];
    foreach ($form_state->getValue('table') as $row) {
      foreach ($row as $cell) {
        $enabled = !empty($cell['enabled']);
        $operation = $cell['operation'];
        $component_id = $cell['component_id'];

        $component_operations[$component_id][$operation] = $enabled;
      }
    }
    $form_state->setValue('component_operations', $component_operations);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $component_operations = $form_state->getValue('component_operations');
    foreach ($component_operations as $component_id => $operations) {
      $component = RuleComponent::load($component_id);

      $configuration = $component->getConfiguration();
      foreach ($operations as $operation => $enabled) {
        $configuration['operations'][$operation] = $enabled;
      }
      $component
        ->setConfiguration($configuration)
        ->save();
    }

    Cache::invalidateTags($this->event->getCacheTagsToInvalidate());
    drupal_set_message($this->t('Updated access operations.'));
  }

}
