<?php

/**
 * @file
 * Contains \Drupal\rng\Plugin\EntityReferenceSelection\RegisterUserSelection.
 */

namespace Drupal\rng\Plugin\EntityReferenceSelection;

use Drupal\Core\Database\Query\SelectInterface;
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
    }
    return $query;
  }

}
