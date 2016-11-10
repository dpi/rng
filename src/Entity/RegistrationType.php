<?php

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
 *     "list_builder" = "\Drupal\rng\Lists\RegistrationTypeListBuilder",
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
    /** @var \Drupal\rng\EventManagerInterface $event_manager */
    $event_manager = \Drupal::service('rng.event_manager');

    /** @var \Drupal\rng\RegistrationTypeInterface $registration_type */
    foreach ($entities as $registration_type) {
      // Remove entity field references in
      // $event->{EventManagerInterface::FIELD_REGISTRATION_TYPE}
      $event_types = $event_manager->getEventTypes();
      foreach ($event_types as $entity_type => $bundles) {
        $event_storage = \Drupal::entityTypeManager()->getStorage($entity_type);
        foreach ($bundles as $bundle => $event_type) {
          $bundle_key = \Drupal::entityTypeManager()
            ->getDefinition($entity_type)->getKey('bundle');

          $ids = $event_storage->getQuery()
            ->condition($bundle_key, $bundle)
            ->condition(EventManagerInterface::FIELD_REGISTRATION_TYPE, $registration_type->id())
            ->execute();

          foreach ($ids as $id) {
            $event_manager
              ->getMeta($event_storage->load($id))
              ->removeRegistrationType($registration_type->id())
              ->save();
          }
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
