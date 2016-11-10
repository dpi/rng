<?php

namespace Drupal\rng\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Entity\EntityManager;
use Drupal\rng\EventManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Cache\Cache;

/**
 * Form controller to reset event access to defaults.
 *
 * Deletes existing access rules and adds default rules back onto the event.
 */
class EventAccessResetForm extends ConfirmFormBase {

  /**
   * The event entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $event;

  /**
   * The event meta wrapper.
   *
   * @var \Drupal\rng\EventMetaInterface
   */
  protected $eventMeta;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new event access reset form.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(EntityManager $entity_manager, EventManagerInterface $event_manager) {
    $this->entityManager = $entity_manager;
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('rng.event_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rng_event_access_reset';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if (!$this->eventMeta->isDefaultRules('rng_event.register')) {
      return $this->t('Are you sure you want to reset access rules to site defaults?');
    }
    else {
      return $this->t('Are you sure you want to customize access rules?');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if (!$this->eventMeta->isDefaultRules('rng_event.register')) {
      return $this->t('Custom access rules will be deleted.');
    }
    else {
      return $this->t('Rules for this event will no longer match site defaults.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    if (!$this->eventMeta->isDefaultRules('rng_event.register')) {
      return $this->t('Delete existing access rules');
    }
    else {
      return $this->t('Customize');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('rng.event.' . $this->event->getEntityTypeId() . '.access',
      [$this->event->getEntityTypeId() => $this->event->id()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $rng_event = NULL) {
    $this->event = clone $rng_event;
    $this->eventMeta = $this->eventManager->getMeta($this->event);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($rules = $this->eventMeta->getRules('rng_event.register')) {
      foreach ($rules as $rule) {
        $rule->delete();
      }
      drupal_set_message($this->t('Access rules reset to site defaults.'));
    }
    else {
      $this->eventMeta->addDefaultAccess();
      drupal_set_message($this->t('Access rules can now be customized using edit operations.'));
    }
    Cache::invalidateTags($this->event->getCacheTagsToInvalidate());
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
