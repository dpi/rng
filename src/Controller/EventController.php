<?php

/**
 * @file
 * Contains \Drupal\rng\Controller\EventController.
 */

namespace Drupal\rng\Controller;

use Drupal\Core\Action\ActionManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Controller for events.
 */
class EventController extends ControllerBase implements ContainerInjectionInterface {
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