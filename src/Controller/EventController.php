<?php

/**
 * @file
 * Contains \Drupal\rng\Controller\EventController.
 */

namespace Drupal\rng\Controller;

use Drupal\Core\Action\ActionManager;
use Drupal\Core\Condition\ConditionManager;
use Drupal\rng\EventManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for events.
 */
class EventController extends ControllerBase implements ContainerInjectionInterface {

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
   * Constructs a new action form.
   *
   * @param \Drupal\Core\Action\ActionManager $actionManager
   *   The action manager.
   * @param \Drupal\Core\Condition\ConditionManager $conditionManager
   *   The condition manager.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(ActionManager $actionManager, ConditionManager $conditionManager, EventManagerInterface $event_manager) {
    $this->actionManager = $actionManager;
    $this->conditionManager = $conditionManager;
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
   * Displays a list of actions which are related to registration access on an
   * event.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route.
   * @param string $event
   *   The parameter to find the event entity.
   *
   * @return array
   *   A render array.
   */
  public function listing_access(RouteMatchInterface $route_match, $event) {
    $event = $route_match->getParameter($event);
    $destination = drupal_get_destination();
    $build = [];
    $rows = [];
    $header = [
      'condition' => ['colspan' => 2, 'data' => $this->t('Condition')],
      'operations' => ['colspan' => 5, 'data' => $this->t('Operations')],
      'scope' => $this->t('Scope'),
    ];

    $build['description'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('The following rules determine who is eligible to register.'),
      '#suffix' => '</p>',
    ];

    $rules = $this->eventManager->getMeta($event)->getRules('rng_event.register');
    foreach($rules as $rule) {
      $row = [];
      $data_types = [];

      foreach ($rule->getConditions() as $condition) {
        $plugin_id = $condition->getPluginId();
        $config = $condition->getConfiguration();


        $definition = $this->conditionManager->getDefinition($plugin_id);
        $row['condition'] = $definition['label'];

        $row['condition_operations']['data'] = ['#type' => 'operations'];
        if ($condition->access('edit')) {
          $row['condition_operations']['data']['#links']['edit'] = [
            'title' => t('Edit'),
            'url' => $condition->urlInfo('edit-form'),
            'query' => $destination,
          ];
        }

        // Warn user actions apply to all registrations if conditions have no
        // entity:registration context.
        $handler = $this->conditionManager->createInstance($plugin_id, $config);
        foreach ($handler->getContextDefinitions() as $context) {
          $data_types[] = $context->getDataType();
        };

        // Support one condition for now.
        break;
      }

      foreach ($rule->getActions() as $action) {
        $conf = $action->getConfiguration();

        $ops = ['create' => NULL, 'view' => NULL, 'update' => NULL, 'delete' => NULL];
        foreach (array_keys($ops) as $op) {
          $message = !empty($conf['operations'][$op]) ? $this->t($op) : '-';
          $row['operation_' . $op] = ($op == 'create' && in_array('entity:registration', $data_types)) ? $this->t('<em>N/A</em>') : $message;
        }

        $row['action_operations']['data'] = ['#type' => 'operations'];
        if ($action->access('edit')) {
          $row['action_operations']['data']['#links']['edit'] = [
            'title' => t('Edit'),
            'url' => $action->urlInfo('edit-form'),
            'query' => $destination,
          ];
        }

        if (!in_array('entity:registration', $data_types) || in_array('rng:event', $data_types)) {
          $row[] = $this->t('<strong>Warning:</strong> selecting view, update, or delete grants operations for any registration on this event.');
        }
        else {
          $row[] = $this->t('For a single registration.');
        }

        // Support one action for now.
        break;
      }
      $rows[] = $row;
    }

    $build['access_list'] = [
      '#type' => 'table',
      '#header' => $header,
      '#title' => $this->t('Access'),
      '#rows' => $rows,
      '#empty' => $this->t('No access rules.'),
    ];

    return $build;
  }

  /**
   * Displays a list of actions which are message or communication related.
   *
   * @param RouteMatchInterface $route_match
   *   The current route.
   * @param string $event
   *   The parameter to find the event entity.
   *
   * @return array
   *   A render array.
   */
  public function listing_messages(RouteMatchInterface $route_match, $event) {
    $event = $route_match->getParameter($event);
    $destination = drupal_get_destination();
    $build = array();
    $header = array(t('When'), t('Do'), t('Operations'));
    $rows = array();

    /* @var ActionManager $manager */
    $manager = \Drupal::service('plugin.manager.action');

    // list of communication related action plugin ids
    $communication_actions = array('rng_registrant_email');

    $rules = $this->eventManager->getMeta($event)->getRules();
    foreach($rules as $rule) {
      /* @var \Drupal\rng\RuleInterface $rule */
      foreach ($rule->getActions() as $action) {
        $row = array();
        $action_id = $action->getPluginId();
        if (in_array($action_id, $communication_actions)) {
          $definition = $manager->getDefinition($action_id);
          $row['trigger'] = $rule->getTriggerID();
          $row['action']['data'] = $definition['label'];

          $row['operations']['data'] = ['#type' => 'operations'];
          if ($action->access('edit')) {
            $row['operations']['data']['#links']['edit'] = [
              'title' => t('Edit'),
              'url' => $action->urlInfo('edit-form'),
              'query' => $destination,
            ];
          }
          if ($rule->access('delete')) {
            $row['operations']['data']['#links']['delete'] = [
              'title' => t('Delete'),
              'url' => $rule->urlInfo('delete-form'),
              'query' => $destination,
            ];
          }
        }
        else {
          continue;
        }

        $rows[] = $row;
      }
    }

    $build['description'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('These messages will be sent when a trigger occurs.'),
      '#suffix' => '</p>',
    ];

    $build['action_list'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#title' => t('Messages'),
      '#rows' => $rows,
      '#empty' => $this->t('No messages found.'),
    );

    return $build;
  }
}