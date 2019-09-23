<?php

namespace Drupal\rng\Event;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Registration event to influence access.
 */
class RegistrationAccessEvent extends Event {

  /**
   * The entity bundle.
   *
   * @var null|string
   */
  protected $entityBundle;

  /**
   * The account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The context.
   *
   * @var array
   */
  protected $context;

  /**
   * Allow access.
   *
   * @var bool
   */
  protected $accessAllowed = TRUE;

  /**
   * RegistrationAccessEvent constructor.
   *
   * @param string $entity_bundle
   *   (optional) The bundle of the entity. Required if the entity supports
   *   bundles, defaults to NULL otherwise.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user session for which to check access, or NULL to check
   *   access for the current user. Defaults to NULL.
   * @param array $context
   *   (optional) An array of key-value pairs to pass additional context when
   *   needed.
   */
  public function __construct($entity_bundle = NULL, AccountInterface $account = NULL, array $context = []) {
    $this->entityBundle = $entity_bundle;
    $this->account = $account;
    $this->context = $context;
  }

  /**
   * Get the entity bundle.
   *
   * @return string|null
   *   The entity bundle or NULL.
   */
  public function getEntityBundle() {
    return $this->entityBundle;
  }

  /**
   * Get the user session for which to check access.
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   *   Gets the user session or NULL.
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * Get the context.
   *
   * @return array
   *   The context.
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * Get if access is allowed.
   *
   * @return bool
   *   True if access is not denied, otherwise FALSE.
   */
  public function isAccessAllowed() {
    return $this->accessAllowed;
  }

  /**
   * Set access.
   *
   * @param bool $access
   *   Boolean if access is allowed.
   */
  public function setAccess($access) {
    $this->accessAllowed = $access;
  }

}
