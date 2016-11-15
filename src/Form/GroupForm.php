<?php

namespace Drupal\rng\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rng\GroupInterface;

/**
 * Form controller for registration groups.
 */
class GroupForm extends ContentEntityForm {

  /**
   * @var \Drupal\rng\GroupInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state, GroupInterface $group = NULL) {
    $group = $this->entity;

    if (!$group->isNew()) {
      $form['#title'] = $this->t('Edit group %label',
        array(
          '%label' => $group->label(),
        )
      );
    }

    $form = parent::form($form, $form_state, $group);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $group = $this->entity
      ->setSource(NULL);
    $event = $group->getEvent();
    $is_new = $group->isNew();
    $group->save();

    $t_args = array('%label' => $group->label());
    if ($is_new) {
      drupal_set_message(t('Group %label has been created.', $t_args));
    }
    else {
      drupal_set_message(t('Group %label was updated.', $t_args));
    }

    $form_state->setRedirect(
      'rng.event.' . $event->getEntityTypeId() . '.group.list',
      array($event->getEntityTypeId() => $event->id())
    );
  }

}
