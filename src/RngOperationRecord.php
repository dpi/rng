<?php

namespace Drupal\rng;

/**
 * Stores operations executed on an entity during a request.
 */
class RngOperationRecord {

  /**
   * The operation executed on this entity.
   *
   * @var string|NULL
   */
  protected $operation;

  /**
   * The entity type ID.
   *
   * @var string|NULL
   */
  protected $entityTypeId;

  /**
   * The entity ID.
   *
   * @var string|NULL
   */
  protected $entityId;

  /**
   * Gets the operation executed on this entity.
   *
   * @return string|NULL
   *   The operation executed on this entity, or NULL if not set.
   */
  public function getOperation() {
    return $this->operation;
  }

  /**
   * Sets the operation executed on this entity.
   *
   * @param string $operation
   *   The operation executed on this entity.
   *
   * @return $this
   *   Return this operation record for chaining.
   */
  public function setOperation($operation) {
    $this->operation = $operation;
    return $this;
  }

  /**
   * Get the entity type ID.
   *
   * @return string|NULL
   *   Gets the entity type ID, or NULL if not set.
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * Set the entity type ID.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return $this
   *   Return this operation record for chaining.
   */
  public function setEntityTypeId($entity_type_id) {
    $this->entityTypeId = $entity_type_id;
    return $this;
  }

  /**
   * Get the entity ID.
   *
   * @return string|NULL
   *   Get the entity ID, or NULL if not set.
   */
  public function getEntityId() {
    return $this->entityId;
  }

  /**
   * Set the entity ID.
   *
   * @param string $entity_id
   *   Sets the entity ID.
   *
   * @return $this
   *   Return this operation record for chaining.
   */
  public function setEntityId($entity_id) {
    $this->entityId = $entity_id;
    return $this;
  }

}
