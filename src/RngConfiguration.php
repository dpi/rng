<?php

namespace Drupal\rng;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\courier\Service\IdentityChannelManagerInterface;

/**
 * The RNG Configuration service.
 */
class RngConfiguration implements RngConfigurationInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The identity channel manager.
   *
   * @var \Drupal\courier\Service\IdentityChannelManagerInterface
   */
  protected $identityChannelManager;

  /**
   * Constructs a new RegistrantFactory object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\courier\Service\IdentityChannelManagerInterface $identity_channel_manager
   *   The identity channel manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, IdentityChannelManagerInterface $identity_channel_manager) {
    $this->configFactory = $config_factory;
    $this->identityChannelManager = $identity_channel_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentityTypes() {
    $config = $this->configFactory->get('rng.settings');
    $identity_types = $config->get('identity_types');
    $allowed_identity_types = is_array($identity_types) ? $identity_types : [];
    $available_identity_types = $this->identityChannelManager->getIdentityTypes();
    return array_intersect($allowed_identity_types, $available_identity_types);
  }

}
