<?php

/**
 * @file
 * Contains \Drupal\rng\Form\GroupForm.
 */

namespace Drupal\rng\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rng\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for registration groups.
 */
class GroupForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state, GroupInterface $group = NULL) {
    $group = $this->getEntity();
    $event = $group->getEvent();

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

  public function save(array $form, FormStateInterface $form_state) {
    $group = $this->getEntity();
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