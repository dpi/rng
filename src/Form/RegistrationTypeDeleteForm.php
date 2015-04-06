<?php

/**
 * @file
 * Contains \Drupal\rng\Form\RegistrationTypeDeleteForm.
 */

namespace Drupal\rng\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for registration types.
 */
class RegistrationTypeDeleteForm extends EntityConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete registration type %label?', array(
      '%label' => $this->entity->label(),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('rng.registration_type.overview');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    drupal_set_message(t('Registration type %label was deleted.', array(
      '%label' => $this->entity->label(),
    )));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
