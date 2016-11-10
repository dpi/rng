<?php

namespace Drupal\rng\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\rng\GroupInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\rng\Exception\InvalidEventException;

/**
 * Defines the application group entity class.
 *
 * @ContentEntityType(
 *   id = "registration_group",
 *   label = @Translation("Registration group"),
 *   handlers = {
 *     "views_data" = "Drupal\rng\Views\RegistrationGroupViewsData",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "access" = "Drupal\rng\AccessControl\GroupAccessControlHandler",
 *     "list_builder" = "\Drupal\rng\Lists\GroupListBuilder",
 *     "form" = {
 *       "default" = "Drupal\rng\Form\GroupForm",
 *       "add" = "Drupal\rng\Form\GroupForm",
 *       "edit" = "Drupal\rng\Form\GroupForm",
 *       "delete" = "Drupal\rng\Form\GroupDeleteForm",
 *     },
 *   },
 *   admin_permission = "administer rng",
 *   base_table = "registration_group",
 *   data_table = "registration_group_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode"
 *   },
 *   links = {
 *     "canonical" = "/rng/group/{registration_group}/edit",
 *     "edit-form" = "/rng/group/{registration_group}/edit",
 *     "delete-form" = "/rng/group/{registration_group}/delete"
 *   }
 * )
 */
class Group extends ContentEntityBase implements GroupInterface {
  /**
   * {@inheritdoc}
   */
  public function getEvent() {
    return $this->get('event')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setEvent(ContentEntityInterface $entity = NULL) {
    $this->set('event', ['entity' => $entity]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isUserGenerated() {
    return $this->getSource() === NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->get('source')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSource($module = NULL) {
    $this->set('source', ['value' => $module]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->set('description', ['value' => $description]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDependentGroups() {
    return $this->groups_dependent->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getConflictingGroups() {
    return $this->groups_conflicting->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
        ->setLabel(t('Group ID'))
      ->setDescription(t('The group ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The group UUID.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The group language code.'));

    $fields['event'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Event'))
      ->setDescription(t('The groups event, or leave empty for global.'))
      ->setReadOnly(TRUE);

    $fields['source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source module'))
      ->setDescription(t('The module which created this group. Or NULL if it is user created.'))
      ->setReadOnly(TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('Name of the group.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('A description of the group.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'text_textfield',
        'weight' => 50,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time that the group was created.'))
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The last time the group was edited.'))
      ->setTranslatable(TRUE);

    $fields['groups_dependent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Dependent groups'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDescription(t('Groups required for this group to be added to a registration.'))
      ->setSetting('target_type', 'registration_group')
      ->setSetting('handler', 'siblings')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 70,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['groups_conflicting'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Conflicting groups'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDescription(t('Groups cannot exist on a registration for this group to be added.'))
      ->setSetting('target_type', 'registration_group')
      ->setSetting('handler', 'siblings')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 75,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   *
   * @param static[] $entities
   *   An array of registration_group entities.
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    /** @var \Drupal\rng\EventManagerInterface $event_manager */
    $event_manager = \Drupal::service('rng.event_manager');

    foreach ($entities as $group) {
      if ($event = $group->getEvent()) {
        // Dont bother if the entity is no longer an event, or event is deleted.
        try {
          $event_meta = $event_manager->getMeta($event);

          // Remove entity field references from the event to group in
          // $event->{EventManagerInterface::FIELD_REGISTRATION_GROUPS}
          $event_meta
            ->removeGroup($group->id())
            ->save();

          // Remove entity field references from registrations to group.
          foreach ($event_meta->getRegistrations() as $registration) {
            $registration
              ->removeGroup($group->id())
              ->save();
          }
        }
        catch (InvalidEventException $e) {
        }
      }
    }
  }

}
