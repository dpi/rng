<?php

namespace Drupal\rng\Breadcrumb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\rng\RegistrantInterface;

/**
 * Provides a breadcrumb builder for registrants.
 */
class RegistrantBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $registrant = $route_match->getParameter('registrant');
    return $registrant instanceof RegistrantInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $links[] = Link::createFromRoute($this->t('Home'), '<front>');

    /** @var \Drupal\rng\RegistrantInterface $registrant */
    $registrant = $route_match->getParameter('registrant');

    if ($registration = $registrant->getRegistration()) {
      if ($event = $registration->getEvent()) {
        $links[] = new Link($event->label(), $event->toUrl());
      }
      $links[] = new Link($registration->label(), $registration->toUrl());
    }

    // Add registrant to the breadcrumb if the current route is not canonical.
    if ('entity.registrant.canonical' != $route_match->getRouteName()) {
      $links[] = new Link($registrant->label(), $registrant->toUrl());
    }

    $breadcrumb = new Breadcrumb();
    return $breadcrumb
      ->setLinks($links)
      ->addCacheContexts(['route.name']);
  }

}
