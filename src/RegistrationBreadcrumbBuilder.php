<?php

/**
 * @file
 * Contains \Drupal\rng\RegistrationBreadcrumbBuilder.
 */

namespace Drupal\rng;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;

/**
 * Provides a breadcrumb builder for RNG
 */
class RegistrationBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entity_manager;

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs the RegistrationBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   */
  public function __construct(EntityManagerInterface $entity_manager, AccessManagerInterface $access_manager, AccountInterface $account) {
    $this->entity_manager = $entity_manager;
    $this->accessManager = $access_manager;
    $this->account = $account;
  }


  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $registration = $route_match->getParameter('registration');
    return $registration instanceof RegistrationInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $links = array(Link::createFromRoute($this->t('Home'), '<front>'));
    $registration = $route_match->getParameter('registration');

    if ($event = $registration->getEvent()) {
      $links[] = new Link($event->label(), $event->urlInfo());
    }

    if ('entity.registration.canonical' != $route_match->getRouteName()) {
      $links[] = new Link($registration->label(), $registration->urlInfo());
    }

    return $links;
  }
}
