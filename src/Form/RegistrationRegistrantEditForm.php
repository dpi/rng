<?php

namespace Drupal\rng\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rng\RegistrationInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Configure registrant settings.
 */
class RegistrationRegistrantEditForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rng_registration_registrant_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RegistrationInterface $registration = NULL) {
    $form['#title'] = $this->t(
      'Edit identities',
      array('@label' => $registration->label())
    );

    $registrants = $registration->getRegistrants();

    $rows = array();
    foreach ($registrants as $registrant) {
      $row = array();
      $identity = $registrant->getIdentity();
      if ($identity instanceof EntityInterface) {
        $url = $identity->urlInfo();
        $row[] = $this->l($identity->label(), $url);
      }
      else {
        $row[] = t('<em>Deleted</em>');
      }
      $row[] = $registrant->id();
      $rows[] = $row;
    }

    $form['registrants'] = array(
      '#type' => 'table',
      '#header' => array($this->t('Identity'), $this->t('Registrant ID')),
      '#rows' => $rows,
      '#empty' => $this->t('No identities associated with this registration.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
