<?php

namespace Drupal\rng\Breadcrumb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\rng\GroupInterface;
use Drupal\Core\Link;
use Drupal\Core\Breadcrumb\Breadcrumb;

/**
 * Provides a breadcrumb builder for groups.
 */
class GroupBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $group = $route_match->getParameter('registration_group');
    return $group instanceof GroupInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $links = array(Link::createFromRoute($this->t('Home'), '<front>'));
    /** @var \Drupal\rng\GroupInterface $group */
    $group = $route_match->getParameter('registration_group');

    if ($event = $group->getEvent()) {
      $links[] = new Link($event->label(), $event->urlInfo());
    }

    $breadcrumb = new Breadcrumb();
    return $breadcrumb
      ->setLinks($links)
      ->addCacheContexts(['route.name']);
  }

}
