<?php

namespace Drupal\rng\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rng\EventManagerInterface;
use Drupal\rng\EventTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Form controller to delete all custom rules for an event type.
 */
class EventTypeRuleDeleteAll extends ConfirmFormBase {

  /**
   * The event type entity.
   *
   * @var \Drupal\rng\EventTypeInterface
   */
  protected $eventType;

  /**
   * Rule storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $ruleStorage;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a EventTypeRuleDeleteAll form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventManagerInterface $event_manager) {
    $this->ruleStorage = $entity_type_manager->getStorage('rng_rule');
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('rng.event_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rng_event_type_rule_delete_all';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete custom access rules for all events?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('All custom rules for events will be deleted. All events will use event type defaults.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete all existing access rules');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.event_type.access_defaults', [
      'event_type' => $this->eventType->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EventTypeInterface $event_type = NULL) {
    $this->eventType = $event_type;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\rng\RuleInterface[] $rules */
    $rules = $this->ruleStorage
      ->loadByProperties([
        'event__target_type' => $this->eventType->getEventEntityTypeId(),
      ]);

    // There is no bundle field on rules. Load all rules one-by-one and find
    // the bundle for each event.
    $count = 0;
    foreach ($rules as $rule) {
      $event = $rule->getEvent();
      // If event no longer exists then delete the rules while we're here.
      if (!$event || $event->bundle() == $this->eventType->getEventBundle()) {
        $rule->delete();
        $count++;
      }
    }

    drupal_set_message($this->formatPlural($count, '@count custom access rule deleted.', '@count custom access rules deleted.'));

    $this->eventManager->invalidateEventType($this->eventType);
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
