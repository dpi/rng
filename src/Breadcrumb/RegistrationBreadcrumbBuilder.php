<?php

namespace Drupal\rng\Breadcrumb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\rng\RegistrationInterface;
use Drupal\Core\Link;
use Drupal\Core\Breadcrumb\Breadcrumb;

/**
 * Provides a breadcrumb builder for registrations.
 */
class RegistrationBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

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

    $breadcrumb = new Breadcrumb();
    return $breadcrumb
      ->setLinks($links)
      ->addCacheContexts(['route.name']);
  }

}
