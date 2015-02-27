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
      $self_id = 'user:' . $current_user->id();
      $form['identity_information']['identity'] = [
        '#type' => 'radios',
        '#title' => $this->t('Identity'),
        '#options' => array(
          $self_id => t('My account: %username', array('%username' => $current_user->getUsername())),
        ),
        '#default_value' => $self_id,
        '#required' => TRUE,
      ];
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
    $registration = $this->getEntity();
    $event = $registration->getEvent();
    $is_new = $registration->isNew();
    $registration->save();

    $t_args = array('@type' => $registration->bundle(), '%label' => $registration->label(), '%id' => $registration->id());

    if ($is_new) {
      $trigger_id = 'entity:registration:new';
      drupal_set_message(t('Registration has been created.', $t_args));

      // Add registrant
      list($entity_type, $entity_id) = explode(':', $form_state->getValue('identity'));
      $identity = entity_load($entity_type, $entity_id);
      if ($identity) {
        $registrant = entity_create('registrant', array(
          'registration' => $registration,
        ));
        $registrant->setIdentity($identity);
        $registrant->save();
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