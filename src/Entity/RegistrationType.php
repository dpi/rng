<?php

/**
 * @file
 * Contains \Drupal\rng\Entity\RegistrationType.
 */

namespace Drupal\rng\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\rng\RegistrationTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\rng\EventManagerInterface;

/**
 * Defines the Registration type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "registration_type",
 *   label = @Translation("Registration type"),
 *   handlers = {
 *     "list_builder" = "\Drupal\rng\RegistrationTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\rng\Form\RegistrationTypeForm",
 *       "edit" = "Drupal\rng\Form\RegistrationTypeForm",
 *       "delete" = "Drupal\rng\Form\RegistrationTypeDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer registration types",
 *   config_prefix = "registration_type",
 *   bundle_of = "registration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/rng/registration_types/manage/{registration_type}",
 *     "edit-form" = "/admin/structure/rng/registration_types/manage/{registration_type}",
 *     "delete-form" = "/admin/structure/rng/registration_types/manage/{registration_type}/delete",
 *   }
 * )
 */
class RegistrationType extends ConfigEntityBundleBase implements RegistrationTypeInterface {

  /**
   * The machine name of this registration type.
   *
   * @var string
   */
  public $id;

  /**
   * The human readable name of this registration type.
   *
   * @var string
   */
  public $label;

  /**
   * A brief description of this registration type.
   *
   * @var string
   */
  public $description;

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    $registration_storage = \Drupal::entityManager()->getStorage('registration');
    $event_type_storage = \Drupal::entityManager()->getStorage('event_type_config');

    /** @var \Drupal\rng\RegistrationTypeInterface $registration_type */
    foreach ($entities as $registration_type) {
      // Remove entity field references in
      // $event->{EventManagerInterface::FIELD_REGISTRATION_TYPE}

      foreach ($event_type_storage->loadMultiple() as $event_config) {
        $bundle_key = \Drupal::entityManager()
          ->getDefinition($event_config->entity_type)->getKey('bundle');
        $event_storage = \Drupal::entityManager()
          ->getStorage($event_config->entity_type);

        $ids = $event_storage->getQuery()
          ->condition($bundle_key, $event_config->bundle)
          ->condition(EventManagerInterface::FIELD_REGISTRATION_TYPE, $registration_type->id())
          ->execute();

        foreach ($ids as $id) {
          $event = $event_storage->load($id);
          $registration_types = &$event->{EventManagerInterface::FIELD_REGISTRATION_TYPE};
          foreach ($registration_types->getValue() as $key => $value) {
            if ($value['target_id'] == $registration_type->id()) {
              $registration_types->removeItem($key);
            }
          }
          $event->save();
        }
      }

      // Remove registrations.
      $ids = $registration_storage->getQuery()
        ->condition('type', $registration_type->id())
        ->execute();

      $registrations = $registration_storage->loadMultiple($ids);
      $registration_storage->delete($registrations);
    }

    parent::preDelete($storage, $entities);
  }

}
