<?php

namespace Drupal\rng\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Action\ActionManager;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rng\EventManagerInterface;
use Drupal\rng\RNGConditionInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;

/**
 * Form for event type default message.
 */
class EventTypeDefaultMessagesListForm extends EntityForm {

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The action manager service.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $actionManager;

  /**
   * The condition manager service.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * Event type rule storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $eventTypeRuleStorage;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Rules for the event type.
   *
   * @var \Drupal\rng\EventTypeRuleInterface[]
   */
  protected $rules;

  /**
   * Constructs a EventTypeAccessDefaultsForm object.
   *
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\Core\Action\ActionManager $actionManager
   *   The action manager.
   * @param \Drupal\Core\Condition\ConditionManager $conditionManager
   *   The condition manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(RedirectDestinationInterface $redirect_destination, ActionManager $actionManager, ConditionManager $conditionManager, EntityTypeManagerInterface $entity_type_manager, EventManagerInterface $event_manager) {
    $this->redirectDestination = $redirect_destination;
    $this->actionManager = $actionManager;
    $this->conditionManager = $conditionManager;
    $this->eventTypeRuleStorage = $entity_type_manager->getStorage('rng_event_type_rule');
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('redirect.destination'),
      $container->get('plugin.manager.action'),
      $container->get('plugin.manager.condition'),
      $container->get('entity_type.manager'),
      $container->get('rng.event_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    /** @var \Drupal\rng\EventTypeInterface $event_type */
    $event_type = $this->entity;
    /** @var array $default_messages */
    $default_messages = $form_state->get('default_messages');
    if (empty($default_messages)) {
      $default_messages = $event_type->getDefaultMessages();
      $form_state->set('default_messages', $default_messages);
    }

    // @TODO : Move this and other occurences into a common place?.
    // @see EventTypeDefaultMessagesAddForm::buildForm.
    $trigger_options = [
      'rng:custom:date' => $this->t('To all registrations, on a date.'),
      (string) $this->t('Registrations') => [
        'entity:registration:new' => $this->t('To a single registration, when it is created.'),
        'entity:registration:update' => $this->t('To a single registration, when it is updated.'),
      ],
    ];
    $trigger_labels = [
      'entity:registration:new' => $this->t('Registration creation'),
      'entity:registration:update' => $this->t('Registration updated'),
      'rng:custom:date' => $this->t('Send on a date'),
    ];

    $form['messages'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'rng-default-messages-wrapper',
      ],
      '#tree' => TRUE,
    ];
    foreach ($default_messages as $key => $message) {
      $form['messages'][$key] = [
        '#type' => 'details',
        '#tree' => TRUE,
        '#title' => $this->t('@label (@status)', [
          '@label' => isset($trigger_labels[$message['trigger']]) ? $trigger_labels[$message['trigger']] : $message['trigger'],
          '@status' => $message['status'] ? $this->t('active') : $this->t('disabled'),
        ]),
      ];
      $form['messages'][$key]['trigger'] = [
        '#type' => 'select',
        '#options' => $trigger_options,
        '#title' => $this->t('Trigger'),
        '#default_value' => $message['trigger'],
      ];
      $form['messages'][$key]['status'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#default_value' => $message['status'],
      ];
      $form['messages'][$key]['subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $message['subject'],
        '#required' => TRUE,
      ];
      $form['messages'][$key]['body'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Body'),
        '#default_value' => $message['body'],
        '#required' => TRUE,
      ];
      $form['messages'][$key]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove this message'),
        '#message_key' => $key,
        '#name' => 'button-message-remove-' . $key,
        '#submit' => ['::removeMessageCallback'],
        '#ajax' => [
          'callback' => '::processMessageCallback',
          'wrapper' => 'rng-default-messages-wrapper',
        ]
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function processMessageCallback(array &$form, FormStateInterface $form_state) {
    // This function may be used for other ajax callbacks, too.
    $triggering_element = $form_state->getTriggeringElement();
    if (strpos($triggering_element['#name'], 'button-message-remove') !== FALSE) {
      unset($form['messages'][$triggering_element['#message_key']]);

      return $form['messages'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeMessageCallback(array &$form, FormStateInterface $form_state) {
    $key = $form_state->getTriggeringElement()['#message_key'];
    $default_messages = $form_state->get('default_messages');
    unset($default_messages[$key]);

    $form_state->set('default_messages', $default_messages);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\rng\EventTypeInterface $event_type */
    $event_type = $this->entity;
    /** @var array $default_messages */
    $default_messages = $form_state->getValue('messages');

    $event_type->setDefaultMessages($default_messages)->save();

    // Site cache needs to be cleared after enabling this setting as there are
    // issue regarding caching.
    Cache::invalidateTags(['rendered']);

    $this->messenger()->addMessage($this->t('Event type default messages saved.'));
    $this->eventManager->invalidateEventType($event_type);
  }
}
