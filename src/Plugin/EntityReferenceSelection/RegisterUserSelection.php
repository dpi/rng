<?php

/**
 * @file
 * Contains \Drupal\rng\Plugin\EntityReferenceSelection\RegisterUserSelection.
 */

namespace Drupal\rng\Plugin\EntityReferenceSelection;

use Drupal\user\Plugin\EntityReferenceSelection\UserSelection;

/**
 * Provides selection for user entities when registering.
 *
 * @EntityReferenceSelection(
 *   id = "rng:register:user",
 *   label = @Translation("User selection"),
 *   entity_types = {"user"},
 *   group = "rng:register",
 *   weight = 10
 * )
*/
class RegisterUserSelection extends UserSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    if (!isset($this->configuration['handler_settings']['event'])) {
      throw new \Exception('Registration identity selection handler requires event context.');
    }
    /* @var \Drupal\Core\Entity\EntityInterface $event */
    $event = $this->configuration['handler_settings']['event'];

    if (empty($event->{RNG_FIELD_EVENT_TYPE_ALLOW_DUPLICATE_REGISTRANTS}->value)) {
      // Remove users that are already registered for event.
      $entity_ids = [];
      $registrant_ids = \Drupal::entityQuery('registrant')
        ->condition('identity__target_type', 'user', '=')
        ->condition('registration.entity.event__target_type', $event->getEntityTypeId(), '=')
        ->condition('registration.entity.event__target_id', $event->id(), '=')
        ->execute();
      foreach (entity_load_multiple('registrant', $registrant_ids) as $registrant) {
        $entity_ids[] = $registrant->getIdentityId()['entity_id'];
      }

      $entity_ids[] = 0; // Remove anonymous user.
      $entity_type = $this->entityManager->getDefinition($this->configuration['target_type']);
      $query->condition($entity_type->getKey('id'), $entity_ids, 'NOT IN');

      // Event access rules.
      $rule_ids = $this->entityManager->getStorage('rng_rule')->getQuery('AND')
        ->condition('event__target_type', $event->getEntityTypeId(), '=')
        ->condition('event__target_id', $event->id(), '=')
        ->condition('trigger_id', 'rng_event.register', '=')
        ->execute();

      // @todo move to event wrapper
      $condition_count = 0;
      $action_manager = \Drupal::service('plugin.manager.condition');
      foreach(entity_load_multiple('rng_rule', $rule_ids) as $rule) {
        foreach ($rule->getActions() as $action) {
          if ($action->getActionID() == 'registration_operations') {
            $conf = $action->getConfiguration();
            if (!empty($conf['operations']['create'])) {
              foreach ($rule->getConditions() as $condition) {
                $condition_count++;
                $condition_instance = $action_manager->createInstance($condition->getActionID(), $condition->getConfiguration());
                $condition_instance->alterQuery($query);
              }
            }
            break;
          }
        }
      }

      // Cancel the query if there are no conditions.
      if (!$condition_count) {
        $query->condition($entity_type->getKey('id') , NULL, 'IS NULL');
      }
    }
    return $query;
  }

}
