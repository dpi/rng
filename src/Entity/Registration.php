<?php

/**
 * @file
 * Contains \Drupal\rng\Entity\Registration.
 */

namespace Drupal\rng\Entity;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\rng\RegistrationInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the registration entity class.
 *
 * @ContentEntityType(
 *   id = "registration",
 *   label = @Translation("Registration"),
 *   bundle_label = @Translation("Registration type"),
 *   base_table = "registration",
 *   data_table = "registration_field_data",
 *   revision_table = "registration_revision",
 *   revision_data_table = "registration_field_revision",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "access" = "Drupal\rng\RegistrationAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\rng\Form\RegistrationForm",
 *       "add" = "Drupal\rng\Form\RegistrationForm",
 *       "edit" = "Drupal\rng\Form\RegistrationForm",
 *       "delete" = "Drupal\rng\Form\RegistrationDeleteForm"
 *     }
 *   },
 *   bundle_entity_type = "registration_type",
 *   admin_permission = "administer registration entity",
 *   permission_granularity = "bundle",
 *   links = {
 *     "canonical" = "/registration/{registration}",
 *     "edit-form" = "/registration/{registration}/edit",
 *     "delete-form" = "/registration/{registration}/delete"
 *   },
 *   field_ui_base_route = "entity.registration_type.edit_form"
 * )
 */
class Registration extends ContentEntityBase implements RegistrationInterface {
  /**
   * {@inheritdoc}
   */
  public function getEventEntityInfo() {
    $entity_info = explode(':', $this->get('event')->value);
    if (count($entity_info) == 2) {
      return array(
        'entity_type' => $entity_info[0],
        'entity_id' => $entity_info[1]
      );
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setEvent(EntityInterface $entity) {
    $this->set('event', $entity->getEntityTypeId() . ':' . $entity->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEvent() {
    if ($info = $this->getEventEntityInfo()) {
      return entity_load($info['entity_type'], $info['entity_id']);
    }
    return NULL;
  }

  public function label() {
    return !empty($this->id->value) ? t('Registration @id', array('@id' => $this->id->value)) : t('New registration');
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Registration ID'))
      ->setDescription(t('The registration ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The registration UUID.'))
      ->setReadOnly(TRUE);

    $fields['vid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The registration revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The registration type.'))
      ->setSetting('target_type', 'registration_type')
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The registration language code.'))
      ->setRevisionable(TRUE);

    // Todo: Replace with DER.
    $fields['event'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Event'))
      ->setDescription(t('Combined event entity type and entity ID. Example: `node:33`.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE) // change to false when https://www.drupal.org/node/2300101 gets in
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('Status of the Registration: 0 = cancelled, 1 = active.'))
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the Registration was created.'))
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE); // change to false when https://www.drupal.org/node/2300101 gets in

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Updated on'))
      ->setDescription(t('The time that the Registration last updated.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (!$this->getEvent() instanceof ContentEntityBase) {
      throw new EntityMalformedException('Invalid or missing event on registration.');
    }
  }
}