<?php

namespace Drupal\rng\Form\Entity;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Url;

/**
 * Form controller to delete a registrant type.
 */
class RegistrantTypeDeleteForm extends EntityDeleteForm  {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete registrant type %label?', array(
      '%label' => $this->entity->label(),
    ));
  }

  /**
   * @inheritDoc
   */
  public function getDescription() {
    return t('Deleting this registrant type will also delete the associated registration.');
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
    return new Url('entity.registrant_type.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\rng\RegistrantTypeInterface $registrant_type */
    $registrant_type = &$this->entity;

    $count = $this->entityTypeManager
      ->getStorage('registrant')
      ->getQuery()
      ->condition('type', $registrant_type->id())
      ->count()
      ->execute();

    if ($count > 0) {
      drupal_set_message($this->t('Cannot delete registrant type.'), 'warning');

      $form['#title'] = $this->getQuestion();
      $form['description'] = [
        '#markup' => $this->formatPlural(
          $count,
          'Unable to delete registrant type. It is used by @count registration.',
          'Unable to delete registrant type. It is used by @count registrations.'
        ),
      ];
    }
    else {
      $form = parent::buildForm($form, $form_state);
    }

    return $form;
  }

}
