<?php

namespace Drupal\rng\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for deleting a registration.
 */
class RegistrationDeleteForm extends ContentEntityConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete this registration?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->urlInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $registration = $this->entity;
    $registration->delete();
    $event = $registration->getEvent();

    drupal_set_message(t('Registration deleted.'));

    if ($urlInfo = $event->urlInfo()) {
      $form_state->setRedirectUrl($urlInfo);
    }
    else {
      $form_state->setRedirect('<front>');
    }
  }

}
