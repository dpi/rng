<?php

namespace Drupal\rng\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\courier\Service\IdentityChannelManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure primary RNG settings.
 */
class RngSettingsForm extends ConfigFormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The identity channel manager.
   *
   * @var \Drupal\courier\Service\IdentityChannelManagerInterface
   */
  protected $identityChannelManager;

  /**
   * Constructs a RegistrantSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\courier\Service\IdentityChannelManagerInterface $identity_channel_manager
   *   The identity channel manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, IdentityChannelManagerInterface $identity_channel_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->identityChannelManager = $identity_channel_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.identity_channel')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rng_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'rng.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('rng.settings');
    $identity_types = $config->get('identity_types');
    $identity_types = is_array($identity_types) ? $identity_types : [];

    $form['contactables'] = [
      '#type' => 'details',
      '#title' => $this->t('People types'),
      '#description' => $this->t('Enable people types who can register for events.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    foreach ($this->identityChannelManager->getIdentityTypes() as $identity_type) {
      $channels = $this->identityChannelManager->getChannelsForIdentityType($identity_type);
      $channels_string = [];
      foreach ($channels as $channel) {
        if ($channel_entity_type = $this->entityTypeManager->getDefinition($channel, FALSE)) {
          $channels_string[] = $channel_entity_type->getLabel();
        }
      }

      if (!$entity_type = $this->entityTypeManager->getDefinition($identity_type, FALSE)) {
        continue;
      }

      $form['contactables'][$identity_type] = [
        '#type' => 'checkbox',
        '#title' => $this->t('@label (@provider)', [
          '@label' => $entity_type->getLabel(),
          '@provider' => $entity_type->getProvider(),
        ]),
        '#description' => $this->t('Supported channels: @channels', [
          '@channels' => implode(', ', $channels_string),
        ]),
        '#default_value' => in_array($identity_type, $identity_types),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $identity_types = [];

    foreach ($form_state->getValue('contactables') as $entity_type => $enabled) {
      if ($enabled) {
        $identity_types[] = $entity_type;
      }
    }

    $config = $this->config('rng.settings');
    $config->set('identity_types', $identity_types);
    $config->save();

    drupal_set_message(t('RNG settings updated.'));
  }

}
