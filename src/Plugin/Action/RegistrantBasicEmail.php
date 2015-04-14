<?php

/**
 * @file
 * Contains \Drupal\rng\Plugin\Action\RegistrantBasicEmail.
 */

namespace Drupal\rng\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\courier\IdentityChannelManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Send an e-mail to all user registrants of a registration.
 *
 * @Action(
 *   id = "rng_registrant_email",
 *   label = @Translation("Send registrant e-mail"),
 *   type = "registration"
 * )
 */
class RegistrantBasicEmail extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * The identity channel manager.
   *
   * @var \Drupal\courier\IdentityChannelManager
   */
  protected $identityChannelManager;

  /**
   * Constructs a RegistrantBasicEmail object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\courier\IdentityChannelManager $identity_channel_manager
   *   The identity channel manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, IdentityChannelManager $identity_channel_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->identityChannelManager = $identity_channel_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('plugin.manager.identity_channel')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'subject' => '',
      'body' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['subject'] = array(
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#default_value' => $this->configuration['subject'],
      '#maxlength' => 128,
      '#description' => t('The subject of the message.'),
    );
    $form['body'] = array(
      '#type' => 'textarea',
      '#title' => t('Message'),
      '#default_value' => $this->configuration['body'],
      '#description' => t('The message that will be sent to each registrant.'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['subject'] = $form_state->getValue('subject');
    $this->configuration['body'] = $form_state->getValue('body');
  }

  /**
   * {@inheritdoc}
   */
  public function execute($context = NULL) {
    /* @var \Drupal\Core\Entity\EntityInterface $event */
    $event = $context['event'];

    /** @var \Drupal\courier\MessageInterface $message_original */
    $message_original = entity_create('courier_email', [
      'subject' => $this->configuration['subject'],
      'body' => $this->configuration['body'],
    ]);

    // @todo: Send meta: reply-to address
    // @todo: $event as token.
    foreach ($context['registrations'] as $registration) {
      /* @var \Drupal\rng\RegistrationInterface $registration */
      foreach ($registration->getRegistrants() as $registrant) {
        $identity = $registrant->getIdentity();
        $message = clone $message_original;
        if ($plugin_id = $this->identityChannelManager->getCourierIdentity('courier_email', $identity->getEntityTypeId())) {
          /** @var \Drupal\courier\Plugin\IdentityChannel\IdentityChannelPluginInterface $plugin */
          $plugin = $this->identityChannelManager->createInstance($plugin_id);
          $plugin->applyIdentity($message, $identity);
          $message->send();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return TRUE;
  }

}
