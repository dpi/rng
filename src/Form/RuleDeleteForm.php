<?php

namespace Drupal\rng\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for deleting a rng rule.
 */
class RuleDeleteForm extends ContentEntityConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete this rule?');
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
    $rule = $this->entity;
    $rule->delete();
    $event = $rule->getEvent();

    drupal_set_message(t('Rule deleted.'));

    if ($urlInfo = $event->urlInfo()) {
      $form_state->setRedirectUrl($urlInfo);
    }
  }

}
