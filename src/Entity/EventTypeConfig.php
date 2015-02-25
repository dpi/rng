<?php

/**
 * @file
 * Contains \Drupal\rng\Entity\EventTypeConfig.
 */

namespace Drupal\rng\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\rng\EventTypeConfigInterface;
use Drupal\Core\Entity\EntityStorageInterface;
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
   * Fields to add to event bundles.
   *
   * @var array
   */
  var $fields = array(
    RNG_FIELD_EVENT_TYPE_REGISTRATION_TYPE,
    RNG_FIELD_EVENT_TYPE_STATUS,
    RNG_FIELD_EVENT_TYPE_CAPACITY,
    RNG_FIELD_EVENT_TYPE_EMAIL_REPLY_TO,
    RNG_FIELD_EVENT_TYPE_ALLOW_DUPLICATE_REGISTRANTS,
  );

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
      module_load_include('inc', 'rng', 'rng.field.defaults');
      foreach ($this->fields as $field) {
        rng_add_event_field_storage($field, $this->entity_type);
        rng_add_event_field_config($field, $this->entity_type, $this->bundle);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    foreach ($this->fields as $field) {
      $field = FieldConfig::loadByName($this->entity_type, $this->bundle, $field);
      if ($field) {
        $field->delete();
      }
    }
    parent::delete();
  }
}