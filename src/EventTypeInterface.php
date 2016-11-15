<?php

namespace Drupal\rng;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a event config entity.
 */
interface EventTypeInterface extends ConfigEntityInterface {

  /**
   * Get event entity type ID.
   *
   * @return string
   *   An entity type ID.
   */
  function getEventEntityTypeId();

  /**
   * Sets the event entity type ID.
   *
   * @param string $entity_type
   *   An entity type ID.
   */
  function setEventEntityTypeId($entity_type);

  /**
   * Get event bundle.
   *
   * @return string
   *   A bundle name.
   */
  function getEventBundle();

  /**
   * Sets the event bundle.
   *
   * @param string $bundle
   *   A bundle name.
   */
  function setEventBundle($bundle);

  /**
   * Gets which permission on event entity grants 'event manage' permission.
   */
  function getEventManageOperation();

  /**
   * Sets operation to mirror from the event entity.
   *
   * @param string $permission
   *   The operation to mirror.
   *
   * @return static
   *   Return this event type for chaining.
   */
  function setEventManageOperation($permission);

  /**
   * Whether to allow event managers to customize default rules.
   *
   * @return boolean
   *   Whether event managers are allowed to customize default rules.
   */
  function getAllowCustomRules();

  /**
   * Set whether event managers can customize default rules.
   *
   * @param boolean $allow
   *   Whether event managers are allowed to customize default rules.
   *
   * @return $this
   *   Return this event type for chaining.
   */
  function setAllowCustomRules($allow);

  /**
   * Registrant type for new registrants associated with this event type.
   *
   * @return string|NULL
   *   The Registrant type used for new registrants associated with this event
   *   type.
   */
  function getDefaultRegistrantType();

  /**
   * Whether a identity type can be created.
   *
   * @param string $entity_type
   *   The identity entity type ID.
   * @param string $bundle
   *   The identity bundle.
   *
   * @return boolean
   *   Whether a identity type can be created.
   */
  public function canIdentityTypeCreate($entity_type, $bundle);

  /**
   * Set whether an identity type can be created.
   *
   * @param string $entity_type
   *   The identity entity type ID.
   * @param string $bundle
   *   The identity bundle.
   * @param boolean $enabled
   *   Whether the identity type can be created.
   *
   * @return $this
   *   Return this event type for chaining.
   */
  public function setIdentityTypeCreate($entity_type, $bundle, $enabled);

  /**
   * Get the form display mode used when the identity is created inline.
   *
   * @param string $entity_type
   *   The identity entity type ID.
   * @param string $bundle
   *   The identity bundle.
   *
   * @return string
   *   The form display mode used when the identity is created inline.
   */
  public function getIdentityTypeEntityFormMode($entity_type, $bundle);

  /**
   * Get the form display modes for creating identities inline.
   *
   * @return array
   *   An array keyed as follows: [entity_type][bundle] = form_mode.
   */
  public function getIdentityTypeEntityFormModes();

  /**
   * Set the form display mode used when the identity is created inline.
   *
   * @param string $entity_type
   *   The identity entity type ID.
   * @param string $bundle
   *   The identity bundle.
   * @param string $form_mode
   *   The form mode ID.
   *
   * @return $this
   *   Return this event type for chaining.
   */
  public function setIdentityTypeEntityFormMode($entity_type, $bundle, $form_mode);

  /**
   * Whether an existing identity type can be referenced.
   *
   * @param string $entity_type
   *   The identity entity type ID.
   * @param string $bundle
   *   The identity bundle.
   *
   * @return boolean
   *   Whether an existing identity type can be referenced.
   */
  public function canIdentityTypeReference($entity_type, $bundle);

  /**
   * Set whether existing identity type can be referenced.
   *
   * @param string $entity_type
   *   The identity entity type ID.
   * @param string $bundle
   *   The identity bundle.
   * @param boolean $enabled
   *   Whether existing identity type can be referenced.
   *
   * @return $this
   *   Return this event type for chaining.
   */
  public function setIdentityTypeReference($entity_type, $bundle, $enabled);

  /**
   * Set registrant type for new registrants associated with this event type.
   *
   * @param string|NULL $registrant_type_id
   *   The Registrant type used for new registrants associated with this event
   *   type.
   *
   * @return $this
   *   Return this event type for chaining.
   */
  function setDefaultRegistrantType($registrant_type_id);

  /**
   * Create or clean up courier_context if none exist for an entity type.
   *
   * @param string $entity_type
   *   Entity type of the event type.
   * @param string $operation
   *   An operation: 'create' or 'delete'.
   */
  static function courierContextCC($entity_type, $operation);

}
