<?php

/**
 * @file
 * Contains \Drupal\rng\Form\ActionForm.
 */

namespace Drupal\rng\Form;

use Drupal\Core\Action\ActionManager;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rng\ActionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for rng actions.
 */
class ActionForm extends ContentEntityForm {
  /* @var ActionInterface $entity */
  var $entity;

  /**
   * Constructs a new action form.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The action manager.
   */
  public function __construct(ActionManager $actionManager) {
    $this->actionManager = $actionManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.action')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->entity->getConfiguration();
    $this->plugin = $this->actionManager->createInstance($this->entity->getActionID(), $config);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $action = $this->getEntity();
    if (!$action->isNew()) {
      $form['#title'] = $this->t('Edit Action',
        array(
          '%action_id' => $action->id()
        )
      );
    }

    $form += $this->plugin->buildConfigurationForm($form, $form_state);
    return parent::form($form, $form_state, $action);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->plugin->submitConfigurationForm($form, $form_state);
  }

  public function save(array $form, FormStateInterface $form_state) {
    $action = $this->getEntity();
    $is_new = $action->isNew();
    $plugin_configuration = $this->plugin->getConfiguration();

    $action->setConfiguration($plugin_configuration);
    $action->save();

    $t_args = array('@type' => $action->bundle(), '%label' => $action->label(), '%id' => $action->id());

    if ($is_new) {
      drupal_set_message(t('Action has been created.', $t_args));
    }
    else {
      drupal_set_message(t('Action was updated.', $t_args));
    }
  }
}