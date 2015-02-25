<?php

/**
 * @file
 * Contains \Drupal\rng\Plugin\Action\RegistrantBasicEmail.
 */

namespace Drupal\rng\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Send an e-mail to all user registrants of a registration.
 *
 * @Action(
 *   id = "rng_registrant_email",
 *   label = @Translation("Registrant e-mail"),
 *   type = "registration"
 * )
 */
class RegistrantBasicEmail extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a RegistrantBasicEmail object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Mail\MailManagerInterface
   *   The mail manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailManagerInterface $mail_manager, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('plugin.manager.mail'),
      $container->get('entity.manager')
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
    $event = $context['event'];
    /* @var \Drupal\rng\RegistrationInterface $registration */
    $registration = $context['registration'];

    foreach ($registration->getRegistrants() as $registrant) {
      $identity = $registrant->getIdentity();

      // @todo: do not assume user entity.
      if ($identity->getEntityTypeId() == 'user') {
        /* @var UserInterface $user */
        $user = $identity;

        // @todo preload storages in constructor
        $storage_user = $this->entityManager->getStorage('user');

        // @todo tokens
        $result = $this->mailManager->mail(
          'system',
          'rng_registrant_email',
          $user->getUsername() . ' <' . $user->getEmail() . '>',
          $user->getPreferredLangcode(),
          array(
            'context' => array(
              'subject' => $this->configuration['subject'],
              'message' => $this->configuration['body'],
            ),
          ),
          NULL // @todo: replyto: get from event wrapper
        );
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
