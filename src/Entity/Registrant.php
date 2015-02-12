<?php

/**
 * @file
 * Contains \Drupal\rng\Entity\Registrant.
 */

namespace Drupal\rng\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
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
  public function getIdentity() {
    return $this->get('identity')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setIdentity(EntityInterface $entity) {
    $this->set('identity', array('entity' => $entity));
    return $this;
  }

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

    $fields['identity'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Identity'))
      ->setDescription(t('The identity for this registrant.'))
      ->setSetting('exclude_entity_types', 'true')
      ->setSetting('entity_type_ids', array('registrant', 'registration'))
      ->setDescription(t('The person associated with this registrant.'))
      ->setReadOnly(TRUE);

    return $fields;
  }
}