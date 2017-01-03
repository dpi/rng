<?php

namespace Drupal\rng\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\rng\EventManagerInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rng\EventMetaInterface;

/**
 * Form controller for registrations.
 */
class RegistrationForm extends ContentEntityForm {

  /**
   * @var \Drupal\rng\RegistrationInterface
   */
  protected $entity;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a registration form.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, EventManagerInterface $event_manager) {
    parent::__construct($entity_manager);
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
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\rng\RegistrationInterface $registration */
    $registration = $this->getEntity();
    $current_user = $this->currentUser();

    $event = $registration->getEvent();
    $event_meta = $this->eventManager->getMeta($event);

    $form = parent::form($form, $form_state);

    if (!$registration->isNew()) {
      $form['#title'] = $this->t('Edit Registration', [
        '%event_label' => $event->label(),
        '%event_id' => $event->id(),
        '%registration_id' => $registration->id(),
      ]);
    }

    $registrants = [];
    if ($registration->isNew()) {
      /** @var \Drupal\rng\RegistrantFactory $registrant_factory */
      $registrant_factory = \Drupal::service('rng.registrant.factory');

      $count = $event_meta->identitiesCanRegister('user', [$current_user->id()]);
      if (count($count) > 0) {
        $registrant = $registrant_factory->createRegistrant([
          'event' => $event,
        ]);

        $current_user = User::load($current_user->id());
        $registrant->setIdentity($current_user);

        $registrants[] = $registrant;
      }
    }
    else {
      $registrants = $registration->getRegistrants();
    }

    $form['registrants_before'] = [
      '#type' => 'value',
      '#value' => $registrants,
    ];

    $form['people'] = [
      '#type' => 'details',
      '#title' => $this->t('People'),
      '#description' => $this->t('Select people to associate with this registration.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $event_type = $event_meta->getEventType();
    $min = $event_meta->getRegistrantsMinimum();
    $max = $event_meta->getRegistrantsMaximum();
    $form['people']['registrants'] = [
      '#type' => 'registrants',
      '#event' => $event,
      '#default_value' => $registrants,
      '#allow_creation' => $event_meta->getCreatableIdentityTypes(),
      '#allow_reference' => $event_meta->getIdentityTypes(),
      '#registrants_minimum' => ($min !== EventMetaInterface::CAPACITY_UNLIMITED) ? $min : NULL,
      '#registrants_maximum' => ($max !== EventMetaInterface::CAPACITY_UNLIMITED) ? $max : NULL,
      '#form_modes' => $event_type->getIdentityTypeEntityFormModes(),
    ];

    if (!$registration->isNew()) {
      $form['revision_information'] = [
        '#type' => 'details',
        '#title' => $this->t('Revisions'),
        '#optional' => TRUE,
        '#open' => $current_user->hasPermission('administer rng'),
        '#weight' => 20,
      ];
      $form['revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#description' => $this->t('Revisions record changes between saves.'),
        '#default_value' => FALSE,
        '#access' => $current_user->hasPermission('administer rng'),
        '#group' => 'revision_information',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\rng\RegistrationInterface $registration */
    $registration = parent::validateForm($form, $form_state);

    /** @var \Drupal\rng\RegistrantInterface[] $registrants_before */
    $registrants_before = $form_state->getValue('registrants_before');
    /** @var \Drupal\rng\RegistrantInterface[] $registrants_after */
    $registrants_after = $form_state->getValue(['people', 'registrants']);

    // Registrants.
    $registrants_after_ids = [];
    foreach ($registrants_after as $registrant) {
      if (!$registrant->isNew()) {
        $registrants_after_ids[] = $registrant->id();
      }
    }

    // Delete old registrants if they are not needed.
    $registrants_delete = [];
    foreach ($registrants_before as $i => $registrant) {
      if (!$registrant->isNew())  {
        if (!in_array($registrant->id(), $registrants_after_ids)) {
          $registrants_delete[] = $registrant;
        }
      }
    }

    $form_state->set('registrants_after', $registrants_after);
    $form_state->set('registrants_delete', $registrants_delete);

    return $registration;
  }


  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $registration = $this->entity;

    $t_args = [
      '@type' => $registration->bundle(),
      '%label' => $registration->label(),
      '%id' => $registration->id(),
    ];

    if (!$registration->isNew()) {
      $registration->setNewRevision(!$form_state->isValueEmpty('revision'));
    }

    if ($registration->save() == SAVED_NEW) {
      drupal_set_message($this->t('Registration has been created.', $t_args));
    }
    else {
      drupal_set_message($this->t('Registration was updated.', $t_args));
    }

    /** @var \Drupal\rng\RegistrantInterface[] $registrants */
    $registrants = $form_state->get('registrants_after');
    foreach ($registrants as $registrant) {
      $registrant->setRegistration($registration);
      $registrant->save();
    }

    /** @var \Drupal\rng\RegistrantInterface[] $registrants_delete */
    $registrants_delete = $form_state->get('registrants_delete');
    foreach ($registrants_delete as $registrant) {
      $registrant->delete();
    }

    if ($registration->access('view')) {
      $form_state->setRedirectUrl($registration->toUrl());
    }
    else {
      $form_state->setRedirect('<front>');
    }
  }

}
