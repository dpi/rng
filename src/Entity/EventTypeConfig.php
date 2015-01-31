<?php

/**
 * @file
 * Contains \Drupal\rng\Entity\EventTypeConfig.
 */

namespace Drupal\rng\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\rng\EventTypeConfigInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Defines the event configuration entity.
 *
 * Event configs hold settings for other fieldable bundles, and store default
 * event setting values for new events.
 *
 * @ConfigEntityType(
 *   id = "event_type_config",
 *   label = @Translation("Event configuration type"),
 *   handlers = {
 *     "list_builder" = "\Drupal\rng\EventTypeConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\rng\Form\EventTypeConfigForm",
 *       "edit" = "Drupal\rng\Form\EventTypeConfigForm",
 *       "delete" = "Drupal\rng\Form\EventTypeConfigDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer event types",
 *   config_prefix = "event_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id"
 *   },
 *   links = {
 *     "edit-form" = "entity.event_type_config.edit",
 *     "delete-form" = "entity.event_type_config.delete"
 *   }
 * )
 */
class EventTypeConfig extends ConfigEntityBase implements EventTypeConfigInterface {

  /**
   * The machine name of this event config.
   *
   * Inspired by two part-ID's from \Drupal\field\Entity\FieldStorageConfig.
   *
   * Config will compute to rng.event.{entity_type}.{bundle}
   *
   * entity_type and bundle are duplicated in file name and config.
   *
   * @var string
   */
  public $id;

  /**
   * The ID of the event entity type.
   *
   * @var string
   */
  public $entity_type;

  /**
   * The ID of the event bundle type.
   *
   * @var string
   */
  public $bundle;

  /**
   * Mirror update permissions.
   *
   * @var boolean
   */
  public $mirror_update_permission;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->entity_type . '.' . $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (!$update) {
      $field_storage = FieldStorageConfig::loadByName($this->entity_type, RNG_FIELD_EVENT_TYPE_REGISTRATION_TYPE);
      if (!$field_storage) {
        $field_storage = entity_create('field_storage_config', array(
          'field_name' => RNG_FIELD_EVENT_TYPE_REGISTRATION_TYPE,
          'type' => 'entity_reference',
          'entity_type' => $this->entity_type,
          'cardinality' => 1,
          'settings' => array(
            'target_type' => 'registration_type',
          ),
        ));
        $field_storage->save();
      }

      $field = FieldConfig::loadByName($this->entity_type, $this->bundle, RNG_FIELD_EVENT_TYPE_REGISTRATION_TYPE);
      if (!$field) {
        $field = entity_create('field_config', array(
          'field_name' => RNG_FIELD_EVENT_TYPE_REGISTRATION_TYPE,
          'entity_type' => $this->entity_type,
          'bundle' => $this->bundle,
          'label' => 'Registration type',
          'settings' => array('handler' => 'default'),
        ));
        $field->save();
      }

      entity_get_display($this->entity_type, $this->bundle, 'default')
        ->setComponent(RNG_FIELD_EVENT_TYPE_REGISTRATION_TYPE)
        ->save();
      entity_get_form_display($this->entity_type, $this->bundle, 'default')
        ->setComponent(RNG_FIELD_EVENT_TYPE_REGISTRATION_TYPE, array(
          'type' => 'entity_reference_autocomplete',
        ))
        ->save();
    }
  }
}