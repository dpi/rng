<?php

/**
 * @file
 * Contains \Drupal\rng\Entity\RegistrationType.
 */

namespace Drupal\rng\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\rng\RegistrationTypeInterface;

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
}