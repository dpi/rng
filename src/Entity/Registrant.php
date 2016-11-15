<?php

namespace Drupal\rng\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\rng\RegistrantInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\rng\RegistrationInterface;

/**
 * Defines the registrant entity class.
 *
 * @ContentEntityType(
 *   id = "registrant",
 *   label = @Translation("Registrant"),
 *   bundle_label = @Translation("Registrant type"),
 *   bundle_entity_type = "registrant_type",
 *   handlers = {
 *     "storage_schema" = "Drupal\rng\RegistrantStorageSchema",
 *     "views_data" = "Drupal\rng\Views\RegistrantViewsData",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\rng\Routing\RegistrantRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\rng\Form\Entity\RegistrantForm",
 *       "edit" = "Drupal\rng\Form\Entity\RegistrantForm",
 *       "delete" = "Drupal\rng\Form\Entity\RegistrantDeleteForm",
 *     },
 *   },
 *   admin_permission = "administer rng",
 *   base_table = "registrant",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   },
 *   field_ui_base_route = "entity.registrant_type.edit_form",
 *   links = {
 *     "canonical" = "/registrant/{registrant}",
 *     "edit-form" = "/registrant/{registrant}/edit",
 *     "delete-form" = "/registrant/{registrant}/delete"
 *   },
 * )
 */
class Registrant extends ContentEntityBase implements RegistrantInterface {

  /**
   * {@inheritdoc}
   */
  public function getRegistration() {
    return $this->get('registration')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRegistration(RegistrationInterface $registration) {
    $this->set('registration', ['entity' => $registration]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentity() {
    return $this->get('identity')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentityId() {
    return array(
      'entity_type' => $this->get('identity')->target_type,
      'entity_id' => $this->get('identity')->target_id,
    );
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
  public function clearIdentity() {
    $this->identity->setValue(NULL);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasIdentity(EntityInterface $entity) {
    $keys = $this->getIdentityId();
    return $entity->getEntityTypeId() == $keys['entity_type'] && $entity->id() == $keys['entity_id'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getRegistrantsIdsForIdentity(EntityInterface $identity) {
    return \Drupal::entityQuery('registrant')
      ->condition('identity__target_type', $identity->getEntityTypeId(), '=')
      ->condition('identity__target_id', $identity->id(), '=')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->id() ? t('Registrant @id', ['@id' => $this->id()]) : t('New registrant');
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['registration'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Registration'))
      ->setDescription(t('The registration associated with this registrant.'))
      ->setSetting('target_type', 'registration')
      ->setCardinality(1)
      ->setReadOnly(TRUE);

    $fields['identity'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Identity'))
      ->setDescription(t('The person associated with this registrant.'))
      ->setSetting('exclude_entity_types', 'true')
      ->setSetting('entity_type_ids', array('registrant', 'registration'))
      ->setCardinality(1)
      ->setReadOnly(TRUE);

    return $fields;
  }

}
