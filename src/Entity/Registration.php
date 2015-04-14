<?php

/**
 * @file
 * Contains \Drupal\rng\Entity\Registration.
 */

namespace Drupal\rng\Entity;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\rng\GroupInterface;
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
 *     "access" = "Drupal\rng\AccessControl\RegistrationAccessControlHandler",
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
   * A cache of registrants.
   *
   * @var integer[]
   */
  protected $registrant_ids;

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
  public function getRegistrantIds() {
    if (!isset($this->registrant_ids)) {
      $this->registrant_ids = \Drupal::entityQuery('registrant')
        ->condition('registration', $this->id(), '=')
        ->execute();
    }
    return $this->registrant_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegistrants() {
    return entity_load_multiple('registrant', $this->getRegistrantIds());
  }

  /**
   * {@inheritdoc}
   */
  public function hasIdentity(EntityInterface $identity) {
    foreach ($this->getRegistrants() as $registrant) {
      if ($registrant->hasIdentity($identity)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function addIdentity(EntityInterface $identity) {
    if ($this->hasIdentity($identity)) {
      // Identity already exists on this registration.
      throw new \Exception('Duplicate identity on registration');
    }
    if ($this->isNew()) {
      // Registration needs an ID before a registrant can be saved.
      throw new \Exception('Registration not saved');
    }
    $registrant = entity_create('registrant', ['registration' => $this])
      ->setIdentity($identity);
    $registrant->save();
    $this->registrant_ids[] = $registrant->id();
    return $registrant;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroups() {
    return $this->groups->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function addGroup(GroupInterface $group) {
    $this->groups->appendItem($group);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeGroup(GroupInterface $group) {
    if (($key = array_search($group, $this->getGroups())) !== FALSE) {
      $this->groups->removeItem($key);
    }
    return $this;
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
      ->setRevisionable(TRUE) // @todo: change to false when https://www.drupal.org/node/2300101 gets in.
      ->setReadOnly(TRUE);

    $fields['groups'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Groups'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDescription(t('The groups the registration is assigned.'))
      ->setSetting('target_type', 'registration_group');

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
      ->setRevisionable(FALSE);

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

    // Add group defaults event settings.
    /* @var $event_meta \Drupal\rng\EventManagerInterface */
    $event_meta = \Drupal::service('rng.event_manager');
    if ($this->isNew()) {
      foreach ($event_meta->getMeta($this->getEvent())->getDefaultGroups() as $group) {
        $this->addGroup($group);
      }
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
