<?php

/**
 * @file
 * Contains \Drupal\rng\EventMetaInterface.
 */

namespace Drupal\rng;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for EventMeta.
 */
interface EventMetaInterface {

  /**
   * Value indicating unlimited registration capacity for an event.
   */
  const CAPACITY_UNLIMITED = -1;

  /**
   * Instantiates a new instance of EventMeta handler.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The event entity.
   *
   * @return static
   *   A new EventMeta instance.
   */
  public static function createInstance(ContainerInterface $container, EntityInterface $entity);

  /**
   * Get the event entity
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The event entity.
   */
  public function getEvent();

  /**
   * Checks if this event is accepting new registrations.
   *
   * This method only checks configuration. Instead you may want to check
   * 'create' operation using entity_access.
   *
   * @return boolean
   *   Whether this event is accepting new registrations.
   */
  public function isAcceptingRegistrations();

  /**
   * Checks if a registrant is allowed to register more than once on this event.
   *
   * @return boolean
   *   Whether duplicate registrants are allowed.
   */
  public function duplicateRegistrantsAllowed();

  /**
   * Gets a list of registration types IDs allowed for this event.
   *
   * @return integer[]
   *   An array of registration_type IDs.
   */
  public function getRegistrationTypeIds();

  /**
   * Gets a list of registration types allowed for this event.
   *
   * @return \Drupal\rng\RegistrationTypeInterface
   *   An array of registration_type entities.
   */
  public function getRegistrationTypes();

  /**
   * Checks if a registration type is allowed to be used on an event.
   *
   * @param \Drupal\rng\RegistrationTypeInterface
   *   A registration type entity.
   *
   * @return boolean
   *   Whether the registration type can be used.
   */
  public function registrationTypeIsValid(RegistrationTypeInterface $registration_type);

  /**
   * Gets configuration for maximum permitted registrations on this event.
   *
   * @return integer|EventMetaInterface::CAPACITY_UNLIMITED
   *   Maximum amount of registrations (>= 0), or unlimited.
   */
  public function getCapacity();

  /**
   * Calculates how many more registrations can be added to this event.
   *
   * This value will not be negative if there are excessive registrations.
   *
   * @return integer|EventMetaInterface::CAPACITY_UNLIMITED
   *   Number of new registrations allowed (>0 0), or unlimited.
   */
  public function remainingCapacity();

  /**
   * Get groups that should be added to all new registrations.
   *
   * @return \Drupal\rng\GroupInterface[]
   *   An array of group entities.
   */
  function getDefaultGroups();

  /**
   * Builds a entity query with conditions referencing this event.
   *
   * Assumes there is a dynamic_entity_reference field on the entity_type named
   * 'event'.
   *
   * @param $entity_type
   *   An entity type with an 'event' DER field attached.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query.
   */
  function buildQuery($entity_type);

  /**
   * Builds a entity query for registrations with conditions referencing this
   * event.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query.
   */
  function buildRegistrationQuery();

  /**
   * Get all registrations for this event.
   *
   * @return \Drupal\rng\RegistrationInterface[]
   *   An array of registration entities.
   */
  function getRegistrations();

  /**
   * Count how many registrations are on this event.
   *
   * @return integer
   *   Number of registrations on this event.
   */
  function countRegistrations();

  /**
   * Builds a entity query for rules with conditions referencing this event.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query.
   */
  function buildRuleQuery();

  /**
   * Get all rules for this event.
   *
   * @param string|NULL $trigger
   *   The trigger ID for the rule.
   *
   * @return \Drupal\rng\RuleInterface[]
   *   An array of rng_rule entities.
   */
  function getRules($trigger = NULL);

  /**
   * Manually triggers rules for this event.
   *
   * @param string $trigger
   *   The trigger ID.
   * @param array $context
   *   Mixed context.
   */
  public function trigger($trigger, $context = array());

  /**
   * Builds a entity query for groups with conditions referencing this event.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query.
   */
  function buildGroupQuery();

  /**
   * Get all groups for this event.
   *
   * @return \Drupal\rng\GroupInterface[]
   *   An array of registration_group entities.
   */
  function getGroups();

  /**
   * Builds a entity query for registrants associated to registrations
   * referencing this event.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query.
   */
  function buildRegistrantQuery();

  /**
   * Get all registrants for this event
   *
   * @return \Drupal\rng\RegistrantInterface[]
   *   An array of registrant entities.
   */
  function getRegistrants();

  /**
   * Count number of identities the current  has proxy register access
   * including himself.
   *
   * @return integer
   *   Number of identities.
   */
  function countProxyIdentities();

  /**
   * Adds default access rules to the event.
   *
   * Access rules determine registration operation grants.
   */
  function addDefaultAccess();
}