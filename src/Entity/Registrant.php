<?php

/**
 * @file
 * Contains \Drupal\rng\Entity\Registrant.
 */

namespace Drupal\rng\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\rng\RegistrantInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the registrant entity class.
 *
 * @ContentEntityType(
 *   id = "registrant",
 *   label = @Translation("Registrant"),
 *   handlers = {
 *   },
 *   admin_permission = "administer rng",
 *   base_table = "registrant",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *   },
 *   field_ui_base_route = "rng.config.registrant"
 * )
 */
class Registrant extends ContentEntityBase implements RegistrantInterface {
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Registrant ID'))
      ->setDescription(t('The registrant ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The registrant UUID.'))
      ->setReadOnly(TRUE);

    $fields['registration'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Registration'))
      ->setSetting('target_type', 'registration')
      ->setReadOnly(TRUE);

    // @todo: the identity
    return $fields;
  }
}