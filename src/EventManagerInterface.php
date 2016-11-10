<?php

namespace Drupal\rng;

use Drupal\Core\Entity\EntityInterface;

/**
 * Event manager for RNG.
 */
interface EventManagerInterface {

  /**
   * ID of an `entity_reference` field attached to an event bundle.
   *
   * Specifies the registration type of registrations that can be created for
   * an event. This field references registration_type entities.
   */
  const FIELD_REGISTRATION_TYPE = 'rng_registration_type';

  /**
   * ID of an `entity_reference` field attached to an event bundle.
   *
   * Specifies the groups that are applied to new registrations. This field
   * references registration_group entities.
   */
  const FIELD_REGISTRATION_GROUPS = 'rng_registration_groups';

  /**
   * ID of an `boolean` field attached to an event bundle.
   *
   * Whether an event is accepting new registrations.
   */
  const FIELD_STATUS = 'rng_status';

  /**
   * ID of an `integer` field attached to an event bundle.
   *
   * The absolute maximum number of registrations that can be created
   * for an event. A negative or missing value indicates unlimited capacity.
   */
  const FIELD_CAPACITY = 'rng_capacity';

  /**
   * ID of an `email` field attached to an event bundle.
   *
   * Reply-to address for e-mails sent from an event.
   */
  const FIELD_EMAIL_REPLY_TO = 'rng_reply_to';

  /**
   * ID of an `boolean` field attached to an event bundle.
   *
   * Whether an event allows a registrant to associate with multiple
   * registrations. An empty value reverts to the site default.
   */
  const FIELD_ALLOW_DUPLICATE_REGISTRANTS = 'rng_registrants_duplicate';

  /**
   * ID of an `integer` field attached to an event bundle.
   *
   * The minimum number of registrants per registration associated.
   */
  const FIELD_REGISTRATION_REGISTRANTS_MINIMUM = 'rng_registrants_minimum';

  /**
   * ID of an `integer` field attached to an event bundle.
   *
   * The maximum number of registrants per registration associated.
   */
  const FIELD_REGISTRATION_REGISTRANTS_MAXIMUM = 'rng_registrants_maximum';

  /**
   * Get the meta instance for an event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An event entity.
   *
   * @return \Drupal\rng\EventMetaInterface|NULL
   *   An event meta object.
   *
   * @throws \Drupal\rng\Exception\InvalidEventException
   *   If the $entity is not an event.
   */
  public function getMeta(EntityInterface $entity);

  /**
   * Determines if an entity is an event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An event entity.
   *
   * @return boolean
   *   Whether the entity is an event.
   */
  public function isEvent(EntityInterface $entity);

  /**
   * Get event type config for an event bundle.
   *
   * Use this to test whether an entity bundle is an event type.
   *
   * @param string $entity_type
   *   An entity type ID.
   * @param string $bundle
   *   A bundle ID.
   *
   * @return \Drupal\rng\EventTypeInterface|null
   */
  function eventType($entity_type, $bundle);

  /**
   * Gets all event types associated with an entity type.
   *
   * @param string $entity_type
   *   An entity type ID.
   *
   * @return \Drupal\rng\EventTypeInterface[]
   *   An array of event type config entities
   */
  function eventTypeWithEntityType($entity_type);

  /**
   * Get all event types configuration entities.
   *
   * @return array
   *   A multidimensional array: [event_entity_type][event_bundle] = $event_type
   */
  function getEventTypes();

  /**
   * Invalidate cache for events types.
   */
  function invalidateEventTypes();

  /**
   * Invalidate cache for an event type.
   *
   * @param \Drupal\rng\EventTypeInterface $event_type
   *   An event type.
   */
  function invalidateEventType(EventTypeInterface $event_type);
}
