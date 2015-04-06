<?php

/**
 * @file
 * Contains \Drupal\rng\Form\EventAccessResetForm.
 */

namespace Drupal\rng\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Entity\EntityManager;
use Drupal\rng\EventManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Form controller to reset event access to defaults.
 *
 * Deletes existing access rules and adds default rules back onto the event.
 */
class EventAccessResetForm extends ConfirmFormBase {

  /**
   * The event entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface $event
   */
  protected $event;

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
    return $this->t('Are you sure you want to delete all access rules and reset access to default settings?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Reset access rules');
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
  public function buildForm(array $form, FormStateInterface $form_state, RouteMatchInterface $route_match = NULL, $event = NULL) {
    $this->event = clone $route_match->getParameter($event);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $event_meta = $this->eventManager->getMeta($this->event);

    // Delete existing rules.
    $query = $event_meta->buildRuleQuery();
    $rules_ids = $query->condition('trigger_id', 'rng_event.register', '=')->execute();
    $rules = $this->entityManager->getStorage('rng_rule')->loadMultiple($rules_ids);
    $this->entityManager->getStorage('rng_rule')->delete($rules);

    // Add back defaults.
    $event_meta->addDefaultAccess();

    $form_state->setRedirectUrl($this->getCancelUrl());
    drupal_set_message(t('Access rules reset.'));
  }

}
