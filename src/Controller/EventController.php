<?php

/**
 * @file
 * Contains \Drupal\rng\Controller\EventController.
 */

namespace Drupal\rng\Controller;

use Drupal\Core\Action\ActionManager;
use Drupal\Core\Condition\ConditionManager;
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
   * Constructs a new action form.
   *
   * @param \Drupal\Core\Action\ActionManager $actionManager
   *   The action manager.
   * @param \Drupal\Core\Condition\ConditionManager $conditionManager
   *   The condition manager.
   */
  public function __construct(ActionManager $actionManager, ConditionManager $conditionManager) {
    $this->actionManager = $actionManager;
    $this->conditionManager = $conditionManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.action'),
      $container->get('plugin.manager.condition')
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
    $build = [];
    $rows = [];
    $header = [
      'condition' => ['colspan' => 2, 'data' => $this->t('Condition')],
      'operations' => ['colspan' => 5, 'data' => $this->t('Operations')],
      'scope' => $this->t('Scope'),
    ];

    $build['description'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('The following rules determine access rights for registrations.'),
      '#suffix' => '</p>',
    ];

    $rule_ids = \Drupal::entityQuery('rng_rule')
      ->condition('event__target_type', $event->getEntityTypeId(), '=')
      ->condition('event__target_id', $event->id(), '=')
      ->condition('trigger_id', 'rng_event.register', '=')
      ->execute();

    /* @var $rule \Drupal\rng\RuleInterface */
    foreach(entity_load_multiple('rng_rule', $rule_ids) as $rule) {
      $row = [];
      $scope_global = TRUE;

      foreach ($rule->getConditions() as $condition) {
        $plugin_id = $condition->getActionID();
        $config = $condition->getConfiguration();


        $definition = $this->conditionManager->getDefinition($plugin_id);
        $row['condition'] = $definition['label'];

        // Links
        $operations = [];
        if ($condition->access('edit') && $condition->hasLinkTemplate('edit-form')) {
          $operations['edit'] = array(
            'title' => t('Edit'),
            'url' => $condition->urlInfo('edit-form'),
          );
        }
        $row['condition_operations']['data'] = array(
          '#type' => 'operations',
          '#links' => $operations,
        );

        // Warn user actions apply to all registrations if conditions have no
        // entity:registration context.
        $handler = $this->conditionManager->createInstance($plugin_id, $config);
        foreach ($handler->getContextDefinitions() as $context) {
          if ($context->getDataType() == 'entity:registration') {
            $scope_global = FALSE;
            break;
          }
        };

        // Support one condition for now.
        break;
      }

      foreach ($rule->getActions() as $action) {
        $conf = $action->getConfiguration();

        $ops = ['create' => NULL, 'view' => NULL, 'update' => NULL, 'delete' => NULL];
        foreach (array_keys($ops) as $op) {
          $row['operation' . $op] = !empty($conf['operations'][$op]) ? $this->t($op) : '';
        }

        // Links
        $operations = [];
        if ($action->access('edit') && $action->hasLinkTemplate('edit-form')) {
          $operations['edit'] = array(
            'title' => t('Edit'),
            'url' => $action->urlInfo('edit-form'),
          );
        }
        $row['action_operations']['data'] = array(
          '#type' => 'operations',
          '#links' => $operations,
        );

        if ($scope_global) {
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
   * @param string $event
   *   The parameter to find the event entity.
   */
  public function listing_messages(RouteMatchInterface $route_match, $event) {
    $event = $route_match->getParameter($event);
    $build = array();
    $header = array(t('When'), t('Do'), t('Operations'));
    $rows = array();

    /* @var ActionManager $manager */
    $manager = \Drupal::service('plugin.manager.action');

    // list of communication related action plugin ids
    $communication_actions = array('rng_registrant_email');

    $rule_ids = \Drupal::entityQuery('rng_rule')
      ->condition('event__target_type', $event->getEntityTypeId(), '=')
      ->condition('event__target_id', $event->id(), '=')
      ->execute();

    foreach(entity_load_multiple('rng_rule', $rule_ids) as $rule) {
      /* @var \Drupal\rng\RuleInterface $rule */
      foreach ($rule->getActions() as $action) {
        $row = array();
        $operations = array();
        $action_id = $action->getActionID();
        $action_configuration = $action->getConfiguration();
        if (in_array($action_id, $communication_actions)) {
          $definition = $manager->getDefinition($action_id);
          $row['trigger'] = $rule->getTriggerID();
          $row['action']['data'] = $definition['label'];
          // operations
          if ($action->access('edit') && $action->hasLinkTemplate('edit-form')) {
            $operations['edit'] = array(
              'title' => t('Edit'),
              'url' => $action->urlInfo('edit-form'),
            );
          }
          if ($rule->access('delete') && $rule->hasLinkTemplate('delete-form')) {
            $operations['delete'] = array(
              'title' => t('Delete'),
              'url' => $rule->urlInfo('delete-form'),
            );
          }
        }

        $row['operations']['data'] = array(
          '#type' => 'operations',
          '#links' => $operations,
        );
        $rows[] = $row;
      }
    }

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