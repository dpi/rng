<?php

/**
 * @file
 * Contains \Drupal\rng\Plugin\EntityReferenceSelection\RegisterUserSelection.
 */

namespace Drupal\rng\Plugin\EntityReferenceSelection;

use Drupal\user\Plugin\EntityReferenceSelection\UserSelection;
use Drupal\rng\RuleGrantsOperationTrait;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Connection;
use Drupal\rng\EventManagerInterface;
use Drupal\Core\Condition\ConditionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rng\RNGConditionInterface;

/**
 * Provides selection for user entities when registering.
 *
 * @EntityReferenceSelection(
 *   id = "rng:register:user",
 *   label = @Translation("User selection"),
 *   entity_types = {"user"},
 *   group = "rng_register",
 *   weight = 10
 * )
*/
class RegisterUserSelection extends UserSelection {

  use RuleGrantsOperationTrait;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * Constructs a new RegisterUserSelection object.
   *
   * {@inheritdoc}
   *
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, Connection $connection, EventManagerInterface $event_manager, ConditionManager $condition_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $module_handler, $current_user, $connection);
    $this->eventManager = $event_manager;
    $this->conditionManager = $condition_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('database'),
      $container->get('rng.event_manager'),
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    if (!isset($this->configuration['handler_settings']['event'])) {
      throw new \Exception('Registration identity selection handler requires event context.');
    }

    $query = parent::buildEntityQuery($match, $match_operator);
    $entity_type = $this->entityManager->getDefinition($this->configuration['target_type']);
    $event_meta = $this->eventManager->getMeta($this->configuration['handler_settings']['event']);

    if (!$event_meta->duplicateRegistrantsAllowed()) {
      // Remove users that are already registered for event.
      $entity_ids = [];

      $registrants = $event_meta->getRegistrants();
      foreach ($registrants as $registrant) {
        $entity_ids[] = $registrant->getIdentityId()['entity_id'];
      }

      $entity_ids[] = 0; // Remove anonymous user.
      $query->condition($entity_type->getKey('id'), $entity_ids, 'NOT IN');
    }

    // Event access rules.
    $condition_count = 0;
    $rules = $event_meta->getRules('rng_event.register');
    foreach($rules as $rule) {
      if ($this->ruleGrantsOperation($rule, 'create')) {
        foreach ($rule->getConditions() as $condition_storage) {
          // Do not use condition if it cannot alter query.
          if (($condition = $condition_storage->createInstance()) instanceof RNGConditionInterface) {
            $condition_count++;
            $condition->alterQuery($query);
          }
        }
      }
    }

    // Cancel the query if there are no conditions.
    if (!$condition_count) {
      $query->condition($entity_type->getKey('id') , NULL, 'IS NULL');
      return $query;
    }

    // Apply proxy registration permissions for the current user.
    $proxy_count = 0;
    $all_users = FALSE; // if user can register `authenticated` users.
    $group = $query->orConditionGroup();

    // Self
    if ($this->currentUser->hasPermission('rng register self')) {
      $proxy_count++;
      $group->condition($entity_type->getKey('id'), $this->currentUser->id(), '=');
    }

    foreach (user_roles(TRUE) as $role) {
      $role_id = $role->id();
      if ($this->currentUser->hasPermission("rng register role $role_id")) {
        if ($role_id == 'authenticated') {
          $all_users = TRUE;
          break;
        }
        else {
          $proxy_count++;
          $group->condition('roles', $role_id, '=');
        }
      }
    }

    if ($all_users) {
      // Do not add any conditions
    }
    else if ($proxy_count) {
      $query->condition($group);
    }
    else {
      // cancel the query
      $query->condition($entity_type->getKey('id') , NULL, 'IS NULL');
    }

    return $query;
  }

}
