<?php

namespace Drupal\rng;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rng\Entity\Registrant;
use Drupal\rng\Plugin\Action\CourierTemplateCollection;
use Drupal\courier\Service\IdentityChannelManagerInterface;

/**
 * Enforces RNG model relationships.
 */
class RngEntityModel implements RngEntityModelInterface {

  /**
   * Storage for registration entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $registrationStorage;

  /**
   * Storage for registrant entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $registrantStorage;

  /**
   * Storage for registration group entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $registrationGroupStorage;

  /**
   * Storage for RNG rule entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $ruleStorage;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * The identity channel manager.
   *
   * @var \Drupal\courier\Service\IdentityChannelManagerInterface
   */
  protected $identityChannelManager;

  /**
   * Record operations for relevant RNG entities.
   *
   * These operations are acted on during request termination.
   *
   * @var \Drupal\rng\RngOperationRecord[]
   */
  protected $operationRecords = [];

  /**
   * Constructs a new RngEntityModel object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   * @param \Drupal\courier\Service\IdentityChannelManagerInterface $identity_channel_manager
   *   The identity channel manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventManagerInterface $event_manager, IdentityChannelManagerInterface $identity_channel_manager) {
    $this->registrationStorage = $entity_type_manager->getStorage('registration');
    $this->registrantStorage = $entity_type_manager->getStorage('registrant');
    $this->registrationGroupStorage = $entity_type_manager->getStorage('registration_group');
    $this->ruleStorage = $entity_type_manager->getStorage('rng_rule');
    $this->eventManager = $event_manager;
    $this->identityChannelManager = $identity_channel_manager;
  }

  /**
   * React to Drupal `hook_entity_insert` or `hook_entity_update` hooks.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object for the entity that is already saved.
   * @param boolean $update
   *   Whether this entity is new.
   *
   * @see _rng_entity_postsave();
   */
  public function hook_entity_postsave(EntityInterface $entity, $update = TRUE) {
    if ($entity instanceof RuleInterface) {
      $this->postSaveRngRule($entity);
    }
    if ($entity instanceof RegistrationInterface) {
      $operation_record = new RngOperationRecord();
      $operation = $update ? 'update' : 'insert';
      $operation_record
        ->setOperation($operation)
        ->setEntityTypeId($entity->getEntityTypeId())
        ->setEntityId($entity->id());
      $this->operationRecords[] = $operation_record;
    }
  }

  /**
   * React to Drupal `hook_entity_predelete` hook.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object for the entity that is about to be deleted.
   *
   * @see hook_entity_predelete();
   */
  public function hook_entity_predelete(EntityInterface $entity) {
    if (in_array($entity->getEntityType(), $this->identityChannelManager->getIdentityTypes())) {
      $this->deletePerson($entity);
    }

    if ($this->eventManager->isEvent($entity)) {
      $this->deleteRngEvent($entity);
    }

    if ($entity instanceof RuleComponentInterface) {
      $this->deleteRngRuleComponent($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOperationRecords() {
    return $this->operationRecords;
  }

  /**
   * Delete related entities when a person entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An identity/person entity.
   */
  protected function deletePerson(EntityInterface $entity) {
    // Remove registrant references to this identity.
    $registrant_ids = Registrant::getRegistrantsIdsForIdentity($entity);
    foreach ($this->registrantStorage->loadMultiple($registrant_ids) as $registrant) {
      /** @var \Drupal\rng\RegistrantInterface $registrant */
      $registrant->clearIdentity();
      $registrant->save();
    }
  }

  /**
   * Delete related entities when an event is deleted.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An RNG event entity.
   */
  protected function deleteRngEvent(EntityInterface $entity) {
    // Don't need to catch exception from getMeta(), it is already checked by
    // the calling method.
    $event_meta = $this->eventManager->getMeta($entity);

    // Delete registrations.
    $registrations = $event_meta->getRegistrations();
    $this->registrationStorage
      ->delete($registrations);

    // Delete groups.
    $groups = $event_meta->getGroups();
    $this->registrationGroupStorage
      ->delete($groups);

    // Delete rules.
    $rules = $event_meta->getRules(NULL, FALSE, NULL);
    $this->ruleStorage
      ->delete($rules);
  }

  /**
   * Update rule scheduler after a rule entity is saved.
   *
   * @param \Drupal\rng\RuleInterface $entity
   *   An RNG rule entity.
   */
  protected function postSaveRngRule(RuleInterface $entity) {
    if (isset($entity->original)) {
      // Don't continue if rule status didn't change.
      if ($entity->isActive() == $entity->original->isActive()) {
        return;
      }
    }

    foreach ($entity->getConditions() as $condition) {
      if ('rng_rule_scheduler' == $condition->getPluginId()) {
        // Create, update, or delete the associated rule scheduler entity if the
        // rule status has changed.

        /** @var \Drupal\rng\Plugin\Condition\RuleScheduler $plugin */
        $plugin = $condition->createInstance();
        $plugin->updateRuleSchedulerEntity();
        $plugin_configuration = $plugin->getConfiguration();
        $condition->setConfiguration($plugin_configuration);
        $condition->save();
      }
    }
  }

  /**
   * Delete related entities when a rule component entity is deleted.
   *
   * @param \Drupal\rng\RuleComponentInterface $entity
   *   An RNG rule component entity.
   */
  protected function deleteRngRuleComponent(RuleComponentInterface $entity) {
    // Delete a TemplateCollection if the entity is a component with
    // configuration for 'rng_courier_message'.
    if ('rng_courier_message' == $entity->getPluginId()) {
      $action = $entity->createInstance();
      if ($action instanceof CourierTemplateCollection) {
        $template_collection = $action->getTemplateCollection();
        if ($template_collection) {
          $template_collection->delete();
        }
      }
    }
  }

}