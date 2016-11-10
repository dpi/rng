<?php

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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $count = $this->entityManager->getStorage('registration')->getQuery()
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();

    if ($count == 0) {
      return parent::buildForm($form, $form_state);
    }

    drupal_set_message($this->t('Cannot delete registration type.'), 'warning');

    $form['#title'] = $this->getQuestion();
    $form['description'] = array(
      '#markup' => $this->formatPlural(
        $count,
        'Unable to delete registration type. It is used by @count registration.',
        'Unable to delete registration type. It is used by @count registrations.'
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    drupal_set_message($this->t('Registration type %label was deleted.', array(
      '%label' => $this->entity->label(),
    )));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
