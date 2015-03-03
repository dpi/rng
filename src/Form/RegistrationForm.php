<?php

/**
 * @file
 * Contains \Drupal\rng\Form\RegistrationForm.
 */

namespace Drupal\rng\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for registrations.
 */
class RegistrationForm extends ContentEntityForm {

  /**
   * @var \Drupal\rng\RegistrationInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $registration = $this->getEntity();
    $event = $registration->getEvent();
    $current_user = $this->currentUser();

    if (!$registration->isNew()) {
      $form['#title'] = $this->t('Edit Registration',
        array(
          '%event_label' => $event->label(),
          '%event_id' => $event->id(),
          '%registration_id' => $registration->id()
        )
      );
    }

    $form = parent::form($form, $form_state, $registration);

    if ($registration->isNew()) {
      $form['identity_information'] = [
        '#type' => 'details',
        '#title' => $this->t('Identity'),
        '#description' => $this->t('Select an identity to associate with this registration.'),
        '#open' => TRUE,
      ];
      $default_identity = $self_id = 'user:' . $current_user->id();
      $form['identity_information']['identity'] = [
        '#type' => 'radios',
        '#options' => NULL,
        '#title' => $this->t('Identity'),
        '#required' => TRUE,
      ];

      // Self
      $form['identity_information']['identity']['self'] = [
        '#type' => 'radio',
        '#title' => t('My account: %username', array('%username' => $current_user->getUsername())),
        '#return_value' => $self_id,
        '#parents' => array('identity'),
        '#default_value' => $default_identity,
      ];

      $entity_types = array('user' => t('User'));
      foreach ($entity_types as $entity_type => $label) {
        $element = 'other_' . $entity_type;
        $form['identity_information']['identity'][$element] = [
          '#prefix' => '<div class="form-item container-inline">',
          '#suffix' => '</div>'
        ];
        $form['identity_information']['identity'][$element]['radio'] = [
          '#type' => 'radio',
          '#title' => $label,
          '#return_value' => "$entity_type:*",
          '#parents' => array('identity'),
          '#default_value' => $default_identity,
        ];
        $form['identity_information']['identity'][$element][$entity_type] = [
          '#type' => 'entity_autocomplete',
          '#title' => $label,
          '#title_display' => 'invisible',
          '#target_type' => $entity_type,
          '#tags' => FALSE,
          '#parents' => array('entity', $entity_type),
        ];
      }

      $form['identity_information']['redirect_identities'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Add additional identities after saving.'),
        '#default_value' => FALSE,
      ];
    }
    else {
      $form['revision_information'] = array(
        '#type' => 'details',
        '#title' => $this->t('Revisions'),
        '#optional' => TRUE,
        '#open' => $current_user->hasPermission('administer rng'),
      );
      $form['revision'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#description' => $this->t('Revisions record changes between saves.'),
        '#default_value' => FALSE,
        '#access' => $current_user->hasPermission('administer rng'),
        '#group' => 'revision_information',
      );
    }

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state) {
    $registration = $this->entity;
    $event = $registration->getEvent();
    $is_new = $registration->isNew();
    $registration->save();

    $t_args = array('@type' => $registration->bundle(), '%label' => $registration->label(), '%id' => $registration->id());

    if ($is_new) {
      $trigger_id = 'entity:registration:new';
      drupal_set_message(t('Registration has been created.', $t_args));

      // Add registrant
      list($entity_type, $entity_id) = explode(':', $form_state->getValue('identity'));
      if ($entity_id == '*') {
        $references = $form_state->getValue('entity');
        if (is_numeric($references[$entity_type])) {
          $entity_id = $references[$entity_type];
        }
      }

      if ($identity = entity_load($entity_type, $entity_id)) {
        $registrant = $registration->addIdentity($identity);
      }
    }
    else {
      $trigger_id = 'entity:registration:update';
      drupal_set_message(t('Registration was updated.', $t_args));
      $registration->setNewRevision(!$form_state->isValueEmpty('revision'));
    }

    rng_rule_trigger($trigger_id, array(
      'event' => $event,
      'registration' => $registration,
    ));

    if ($registration->id()) {
      if ($registration->access('view')) {
        $route_name = $form_state->getValue('redirect_identities') ? 'entity.registration.registrants' : 'entity.registration.canonical';
        $form_state->setRedirect($route_name, array('registration' => $registration->id()));
      }
      else {
        $form_state->setRedirect('<front>');
      }
    }
  }
}