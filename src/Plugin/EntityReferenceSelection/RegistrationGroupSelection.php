<?php

namespace Drupal\rng\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\SelectionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Provides specific access control for registration groups.
 *
 * @EntityReferenceSelection(
 *   id = "default:registration_group",
 *   label = @Translation("Registration group selection"),
 *   entity_types = {"registration_group"},
 *   group = "default",
 *   weight = 1
 * )
 */
class RegistrationGroupSelection extends SelectionBase {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    if (($event = $this->configuration['entity']) instanceof EntityInterface) {
      $group = $query->andConditionGroup()
        ->condition('event__target_type', $event->getEntityTypeId(), '=')
        ->condition('event__target_id', $event->id(), '=');
      $query->condition($group);
      $query->condition('source', NULL, 'IS NULL');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) {
    // @todo, allow global groups via query alter
    // ->condition('event__target_type', NULL, 'IS NULL')
    // ->condition('event__target_id', NULL, 'IS NULL');
  }

}
