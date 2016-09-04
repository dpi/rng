<?php

namespace Drupal\rng\Form\Entity;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rng\RegistrantInterface;

/**
 * Form controller for registrants.
 */
class RegistrantForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state, RegistrantInterface $registrant = NULL) {
    $form = parent::form($form, $form_state);
    $registrant = $this->entity;

    if ($registrant && !$registrant->isNew()) {
      $form['#title'] = $this->t('Edit registrant');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $registrant = $this->entity;
    $is_new = $registrant->isNew();
    $registrant->save();

    if ($is_new) {
      drupal_set_message($this->t('Registrant created.'));
    }
    else {
      drupal_set_message($this->t('Registrant updated.'));
    }
  }

}
