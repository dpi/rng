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
class EventTypeDefaultMessagesForm extends EntityForm {

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

    $default_massages = $event_type->getDefaultMessages();
    // TODO: create form to configure Default messages.

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\rng\EventTypeInterface $event_type */
    $event_type = $this->entity;

    // TODO: save default messages.
    $messages = array();

    $event_type->setDefaultMessages($messages)->save();

    // Site cache needs to be cleared after enabling this setting as there are
    // issue regarding caching.
    // For some reason actions access is not reset if pages are rendered with no
    // access/viability.
    Cache::invalidateTags(['rendered']);

    drupal_set_message($this->t('Event type access defaults saved.'));
    $this->eventManager->invalidateEventType($event_type);
  }
}
