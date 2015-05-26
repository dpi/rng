<?php

/**
 * @file
 * Contains \Drupal\rng\Plugin\Action\CourierTemplateCollection.
 */

namespace Drupal\rng\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\courier\IdentityChannelManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Creates a template collection and provides a user interface to its templates.
 *
 * @Action(
 *   id = "rng_courier_message",
 *   label = @Translation("Send message"),
 *   type = "registration"
 * )
 */
class CourierTemplateCollection extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\courier\IdentityChannelManager $identity_channel_manager
   *   The identity channel manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, IdentityChannelManager $identity_channel_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entity_manager;
    $this->identityChannelManager = $identity_channel_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity.manager'),
      $container->get('plugin.manager.identity_channel')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   - template_collection: integer: ID of a courier_template_collection
   *     entity. Automatically filled after first submission.
   *   - active: boolean: Whether the templates associated with the template
   *     collection are ready to be sent. The message will not be sent until
   *     this is set to true.
   */
  public function defaultConfiguration() {
    return array(
      'template_collection' => NULL,
      'active' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // @todo: replace w/ inline entity forms.
    $form['#title'] = $this->t('Edit templates');
    $form['description']['#markup'] = $this->t('Registrants have an option to choose which channel they will receive the message.
Each template requires content suitable to the channel.');

    $form['links'] = array(
      '#title' => $this->t('Channels'),
      '#theme' => 'item_list',
      '#items' => [],
    );

    $configuration = $this->getConfiguration();
    if ($template_collection = $this->getTemplateCollection()) {
      foreach ($template_collection->getTemplates() as $entity) {
        $item = [];
        $item[] = [
          '#type' => 'link',
          '#title' => $entity->getEntityType()->getLabel(),
          '#url' => $entity->urlInfo('edit-form'),
        ];

        $form['links']['#items'][] = $item;
      }
    }

    $form['draft'] = [
      '#type' => 'checkbox',
      '#title' => 'Draft',
      '#default_value' => empty($configuration['active']),
      '#description' => 'Uncheck when all templates are ready.',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['active'] = $form_state->getValue('draft') === 0;

    $configuration = $this->getConfiguration();

    // Create new
    if (!isset($configuration['template_collection'])) {

      /** @var \Drupal\courier\TemplateCollectionInterface $template_collection */
      $template_collection = $this->entityManager->getStorage('courier_template_collection')->create(
        ['template' => 'rng_custom']
      );
      $template_collection->save();

      if (!$template_collection->isNew()) {
        $templates[] = $this->entityManager->getStorage('courier_email')->create();
        foreach ($templates as $template) {
          $template_collection->setTemplate($template);
        }
        $template_collection->save();
      }

      $this->configuration['template_collection'] = $template_collection->id();
    }
  }

  /**
   * Sends the message.
   *
   * @param array $context
   *   An associative array defining context.
   *   - \Drupal\rng\RegistrationInterface[] registrations: An array of
   *     registrations to send the message.
   */
  public function execute($context = NULL) {
    if ($this->isActive() && ($template_collection = $this->getTemplateCollection())) {
      /* @var \Drupal\rng\RegistrationInterface $registration */
      foreach ($context['registrations'] as $registration) {
        foreach ($registration->getRegistrants() as $registrant) {
          $identity = $registrant->getIdentity();
          $this->identityChannelManager->sendMessage($template_collection, $identity);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return FALSE;
  }

  /**
   * Get the courier_template_collection object associated with this action.
   *
   * @return \Drupal\courier\TemplateCollectionInterface|NULL
   *   A courier_template_collection object. NULL if it has not been created.
   */
  public function getTemplateCollection() {
    if (isset($this->configuration['template_collection'])) {
      return $this->entityManager->getStorage('courier_template_collection')->load($this->configuration['template_collection']);
    }
    return NULL;
  }

  /**
   * Whether the templates in the collection are ready to be sent/no longer in
   * draft mode.
   *
   * @return bool
   *   Whether the template collection is active.
   */
  protected function isActive() {
    return !empty($this->configuration['active']);
  }
}
