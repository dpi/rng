<?php

/**
 * @file
 * Contains \Drupal\rng\Entity\Registration.
 */

namespace Drupal\rng\Entity;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
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
 *     "views_data" = "Drupal\rng\Views\RegistrationViewsData",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "access" = "Drupal\rng\RegistrationAccessControlHandler",
 *     "list_builder" = "\Drupal\rng\RegistrationListBuilder",
 *     "form" = {
 *       "default" = "Drupal\rng\Form\RegistrationForm",
 *       "add" = "Drupal\rng\Form\RegistrationForm",
 *       "edit" = "Drupal\rng\Form\RegistrationForm",
 *       "delete" = "Drupal\rng\Form\RegistrationDeleteForm",
 *       "registrants" = "Drupal\rng\Form\RegistrationRegistrantEditForm"
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
  public function getEvent() {
    return $this->get('event')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setEvent(ContentEntityInterface $entity) {
    $this->set('event', array('entity' => $entity));
    return $this;
  }

  public function label() {
    return !empty($this->id->value) ? t('Registration @id', array('@id' => $this->id->value)) : t('New registration');
  }

  /**
   * {@inheritdoc}
   */
  public function getRegistrants() {
    $registrant_ids = \Drupal::entityQuery('registrant')
      ->condition('registration', $this->id(), '=')
      ->execute();
    return entity_load_multiple('registrant', $registrant_ids);
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

    $fields['event'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Event'))
      ->setDescription(t('The registration type.'))
      ->setSetting('exclude_entity_types', 'true')
      ->setSetting('entity_type_ids', array('registrant', 'registration'))
      ->setDescription(t('The relationship between this registration and an event.'))
      ->setRevisionable(TRUE) // change to false when https://www.drupal.org/node/2300101 gets in
      ->setReadOnly(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('Status of the Registration: 0 = cancelled, 1 = active.'))
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('Time the Registration was created.'))
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE); // change to false when https://www.drupal.org/node/2300101 gets in

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Updated on'))
      ->setDescription(t('The time Registration was last updated.'))
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

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Delete associated registrants.
    $registrant_ids = \Drupal::entityQuery('registrant')
      ->condition('registration', $this->id(), '=')
      ->execute();
    $registrants = entity_load_multiple('registrant', $registrant_ids);
    foreach ($registrants as $registrant) {
      $registrant->delete();
    }

    parent::delete();
  }
}