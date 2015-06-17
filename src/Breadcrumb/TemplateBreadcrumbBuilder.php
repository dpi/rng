<?php

/**
 * @file
 * Contains \Drupal\rng\Breadcrumb\TemplateBreadcrumbBuilder.
 */

namespace Drupal\rng\Breadcrumb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\courier\IdentityChannelManagerInterface;
use Drupal\rng\EventManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\courier\ChannelInterface;
use Drupal\courier\Entity\TemplateCollection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\courier\TemplateCollectionInterface;
use Drupal\rng\Plugin\Action\CourierTemplateCollection;
use Drupal\Core\Link;

/**
 * Provides a breadcrumb builder for templates related to an event.
 */
class TemplateBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The identity channel manager.
   *
   * @var \Drupal\courier\IdentityChannelManagerInterface
   */
  protected $identityChannelManager;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs the RegistrationBreadcrumbBuilder.
   *
   * @param \Drupal\courier\IdentityChannelManagerInterface $identity_channel_manager
   *   The identity channel manager.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(IdentityChannelManagerInterface $identity_channel_manager, EventManagerInterface $event_manager) {
    $this->identityChannelManager = $identity_channel_manager;
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if ($template_collection = $this->getTemplateCollection($route_match)) {
      $owner = $template_collection->getOwner();
      return $this->eventManager->isEvent($owner);
    }
    return FALSE;
  }

  /**
   * Get template collection for the template at current route.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return \Drupal\courier\TemplateCollectionInterface|NULL
   *   The template collection entity for this route, or NULL.
   */
  private function getTemplateCollection(RouteMatchInterface $route_match) {
    if ($template = $this->getTemplate($route_match)) {
      $template_collection = TemplateCollection::getTemplateCollectionForTemplate($template);
      return $template_collection;
    }
    return NULL;
  }

  /**
   * Determines if the current route is for a template entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return \Drupal\courier\ChannelInterface|NULL
   *   The template entity for this route, or NULL.
   */
  private function getTemplate(RouteMatchInterface $route_match) {
    $channels = $this->identityChannelManager->getChannels();
    foreach (array_keys($channels) as $entity_type_id) {
      $entity = $route_match->getParameter($entity_type_id);
      if ($entity instanceof ChannelInterface) {
        return $entity;
      }
    }
    return NULL;
  }

  /**
   * Get the rule component which references the template collection.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   An event entity
   * @param \Drupal\courier\TemplateCollectionInterface $template_collection
   *   A template collection
   *
   * @return \Drupal\rng\RuleComponentInterface|NULL
   *   A rule component, or NULL if the template collection does not belong to
   *   this event.
   */
  private function getComponent(EntityInterface $event, TemplateCollectionInterface $template_collection) {
    $rules = $this->eventManager->getMeta($event)->getRules();
    foreach ($rules as $rule) {
      /* @var \Drupal\rng\RuleInterface $rule */
      foreach ($rule->getActions() as $component) {
        if ($component->getPluginId() == 'rng_courier_message') {
          $action = $component->createInstance();
          if (($action instanceof CourierTemplateCollection) && ($action->getTemplateCollection() == $template_collection)) {
            return $component;
            break 2;
          }
        }
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $links = array(Link::createFromRoute($this->t('Home'), '<front>'));
    $template_collection = $this->getTemplateCollection($route_match);
    $template = $this->getTemplate($route_match);

    if ($event = $template_collection->getOwner()) {
      $links[] = new Link($event->label(), $event->urlInfo());
    }

    // Locate which plugin contains a reference to the template collection.
    if ($component = $this->getComponent($event, $template_collection)) {
      $links[] = new Link($this->t('Templates'), $component->urlInfo());
    }

    // Add breadcrumb to entity if not on the canonical route.
    $urlInfo = $template->urlInfo();
    if ($urlInfo->getRouteName() != $route_match->getRouteName()) {
      $links[] = new Link($template->label(), $urlInfo);
    }

    return $links;
  }

}