<?php

namespace Drupal\rng\Breadcrumb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\rng\RuleComponentInterface;
use Drupal\Core\Link;
use Drupal\Core\Breadcrumb\Breadcrumb;

/**
 * Provides a breadcrumb builder for RNG rule components.
 */
class RuleComponentBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $component = $route_match->getParameter('rng_rule_component');
    return $component instanceof RuleComponentInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $links = array(Link::createFromRoute($this->t('Home'), '<front>'));
    /** @var \Drupal\rng\RuleComponentInterface $component */
    $component = $route_match->getParameter('rng_rule_component');
    if ($rule = $component->getRule()) {
      if ($event = $rule->getEvent()) {
        $links[] = new Link($event->label(), $event->urlInfo());
      }
    }

    $breadcrumb = new Breadcrumb();
    return $breadcrumb
      ->setLinks($links)
      ->addCacheContexts(['route.name']);
  }

}
