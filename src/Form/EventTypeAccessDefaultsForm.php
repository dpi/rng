<?php

namespace Drupal\rng\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Action\ActionManager;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rng\EventManagerInterface;
use Drupal\rng\RNGConditionInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;

/**
 * Form for event type access defaults.
 */
class EventTypeAccessDefaultsForm extends EntityForm {

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

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
   * Event type rule storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $eventTypeRuleStorage;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Rules for the event type.
   *
   * @var \Drupal\rng\EventTypeRuleInterface[]
   */
  protected $rules;

  /**
   * Constructs a EventTypeAccessDefaultsForm object.
   *
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\Core\Action\ActionManager $actionManager
   *   The action manager.
   * @param \Drupal\Core\Condition\ConditionManager $conditionManager
   *   The condition manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(RedirectDestinationInterface $redirect_destination, ActionManager $actionManager, ConditionManager $conditionManager, EntityTypeManagerInterface $entity_type_manager, EventManagerInterface $event_manager) {
    $this->redirectDestination = $redirect_destination;
    $this->actionManager = $actionManager;
    $this->conditionManager = $conditionManager;
    $this->eventTypeRuleStorage = $entity_type_manager->getStorage('rng_event_type_rule');
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('redirect.destination'),
      $container->get('plugin.manager.action'),
      $container->get('plugin.manager.condition'),
      $container->get('entity_type.manager'),
      $container->get('rng.event_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['table'] = [
      '#type' => 'table',
      '#empty' => $this->t('No access rules.'),
    ];

    /** @var \Drupal\rng\EventTypeInterface $event_type */
    $event_type = $this->entity;

    $trigger = 'rng_event.register';

    // Header.
    $form['table']['head'] = [
      '#attributes' => [],
      [
        '#wrapper_attributes' => ['header' => TRUE, 'rowspan' => 2],
        '#plain_text' => $this->t('Rule'),
      ],
      [
        '#wrapper_attributes' => ['header' => TRUE, 'rowspan' => 2, 'colspan' => 2],
        '#plain_text' => $this->t('Condition'),
      ],
      // Love to have 'Condition' column as colspan=3 and omit this header, but
      // theme renders 'operations' widget incorrectly when only one link is
      // present.
      [
        '#wrapper_attributes' => ['header' => TRUE, 'rowspan' => 2, 'width' => 100],
        '#plain_text' => $this->t('Links'),
      ],
      [
        '#wrapper_attributes' => ['header' => TRUE, 'rowspan' => 2, 'colspan' => 2],
        '#plain_text' => $this->t('Scope'),
      ],
      [
        '#wrapper_attributes' => ['header' => TRUE, 'rowspan' => 1, 'colspan' => 4],
        '#plain_text' => $this->t('Operations'),
      ],
    ];

    $operations = ['create' => $this->t('Create'), 'view' => $this->t('View'), 'update' => $this->t('Update'), 'delete' => $this->t('Delete')];
    foreach ($operations as $operation) {
      $form['table']['operations'][] = [
        '#wrapper_attributes' => ['header' => TRUE, 'class' => ['checkbox']],
        '#plain_text' => $operation,
      ];
    }

    $i = 0;

    $this->rules = $this->eventTypeRuleStorage
      ->loadByProperties([
        'entity_type' => $event_type->getEventEntityTypeId(),
        'bundle' => $event_type->getEventBundle(),
        'trigger' => $trigger,
      ]);

    foreach ($this->rules as $rule_id => $rule) {
      $i++;
      $scope_all = FALSE;
      $supports_create = 0;
      $condition_context = [];

      // Conditions.
      $k = 0;

      $row = [];
      $row['rule'] = [
        '#wrapper_attributes' => ['header' => FALSE],
        '#plain_text' => $this->t('@row.', ['@row' => $i]),
      ];

      foreach ($rule->getConditions() as $id => $condition) {
        $k++;
        $row[] = [
          '#wrapper_attributes' => ['header' => TRUE],
          '#markup' => $this->t('@condition.', ['@condition' => $k])
        ];

        $condition = $this->conditionManager
          ->createInstance($condition['id'], $condition);

        $condition_context += array_keys($condition->getContextDefinitions());
        $scope_all = (!in_array('registration', $condition_context) || in_array('event', $condition_context));

        if ($condition instanceof RNGConditionInterface) {
          $supports_create++;
        }

        $row[] = [
          '#markup' => $condition->summary(),
        ];

        $row[] = [
          '#type' => 'operations',
          '#links' => ['edit' => [
            'title' => t('Edit'),
            'url' => Url::fromRoute('rng.rng_event_type_rule.component.edit', [
              'rng_event_type_rule' => $rule->id(),
              'component_type' => 'condition',
              'component_id' => $id,
            ]),
            'query' =>$this->redirectDestination->getAsArray(),
          ]]
        ];

        // Scope: warn user actions apply to all registrations.
        if ($scope_all) {
          $row[] = [
            '#wrapper_attributes' => ['colspan' => 2],
            '#plain_text' => $this->t('Single registration'),
          ];
        } else {
          $row[] = [
            '#plain_text' => $this->t('All registrations.'),
          ];
          $row[] = [
            '#wrapper_attributes' => ['width' => '20%'],
            '#markup' => $this->t('<strong>Warning:</strong> selecting view, update, or delete grants access to any registration on this event.'),
          ];
        }

        if ($k == 1) {
          $row = array_merge($row, $this->actionRows($rule, $operations, $scope_all, $supports_create));
        }

        $form['table'][] = $row;

        $row = [];
      }

    }

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
    ];
    $form['settings']['custom_rules'] = [
      '#title' => $this->t('Allow default rules customization'),
      '#description' => $this->t('Allow event managers to customize event default rules. Changing this setting does not affect existing rules.'),
      '#type' => 'checkbox',
      '#default_value' => $event_type->getAllowCustomRules(),
    ];

    return $form;
  }

  function actionRows($rule, array $operations, $scope_all, $supports_create) {
    $rowspan = count($rule->getConditions());

    // Actions.
    if ($action = $rule->getAction('registration_operations')) {
      $row = [];

      // Operations granted.
      $config = $action['configuration'];
      foreach ($operations as $op => $t) {
        $disabled = $op == 'create' && ($supports_create != count($rule->getConditions()));
        if ($disabled) {
          $row[] = [
            '#plain_text' => $this->t('N/A'),
            '#wrapper_attributes' => ['class' => ['checkbox']],
          ];
        }
        else {
          $row[] = [
            '#type' => 'checkbox',
            '#title' => $this->t('@operation', ['@operation' => $t]),
            '#title_display' => 'invisible',
            '#default_value' => !empty($config['operations'][$op]),
            '#parents' => ['actions', 'operations', $rule->getMachineName(), $op],
            '#wrapper_attributes' => ['class' => ['checkbox'], 'rowspan' => $rowspan],
          ];
        }
      }

      return $row;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\rng\EventTypeInterface $event_type */
    $event_type = $this->entity;
    $registration_operations = $form_state->getValue(['actions', 'operations']);

    foreach ($this->rules as $rule) {
      $rule->setAction('registration_operations', [
        'id' => 'registration_operations',
        'configuration' => ['operations' => $registration_operations[$rule->getMachineName()]],
      ]);
      $rule->save();
    }

    $event_type
      ->setAllowCustomRules($form_state->getValue('custom_rules'))
      ->save();

    // Site cache needs to be cleared after enabling this setting as there are
    // issue regarding caching.
    // For some reason actions access is not reset if pages are rendered with no
    // access/viability.
    Cache::invalidateTags(['rendered']);

    drupal_set_message($this->t('Event type access defaults saved.'));
    $this->eventManager->invalidateEventType($event_type);
  }

  /**
   * {@inheritdoc}
   *
   * Remove delete element since it is confusing on non CRUD forms.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\rng\EventTypeInterface $event_type */
    $event_type = $this->entity;

    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);

    $actions['delete-custom-rules'] = array(
      '#type' => 'link',
      '#title' => $this->t('Delete all custom rules'),
      '#attributes' => array(
        'class' => array('button', 'button--danger'),
      ),
    );

    $actions['delete-custom-rules']['#url'] = Url::fromRoute('entity.event_type.access_defaults.delete_all', [
      'event_type' => $event_type->id(),
    ]);

    return $actions;
  }

}
