<?php

namespace Drupal\rng\Cache\Context;

use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * A context for the current rng_event.
 *
 * Cache context ID: 'rng_event'.
 */
class RngEventCacheContext implements CacheContextInterface {

  /**
   * The RNG event from the current route.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $rng_event;

  /**
   * Constructs a new RngEventCacheContext service..
   *
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $context_provider
   *   The rng_event context service.
   */
  public function __construct(ContextProviderInterface $context_provider) {
    /** @var \Drupal\rng\ContextProvider\RngEventRouteContext $context_provider */
    $contexts = $context_provider->getRuntimeContexts(['rng_event']);
    $this->rng_event = $contexts['rng_event']->getContextValue();
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('RNG Event');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    if (isset($this->rng_event)) {
      return $this->rng_event->getEntityTypeId() . ':' . $this->rng_event->id();
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
