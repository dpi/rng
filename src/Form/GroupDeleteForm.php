<?php

namespace Drupal\rng\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for deleting a registration group.
 */
class GroupDeleteForm extends ContentEntityConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete this group?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->getEvent()->urlInfo();
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
    $group = $this->entity;
    $group->delete();
    $event = $group->getEvent();

    drupal_set_message(t('Group deleted.'));

    $form_state->setRedirect(
      'rng.event.' . $event->getEntityTypeId() . '.group.list',
      array($event->getEntityTypeId() => $event->id())
    );
  }

}
