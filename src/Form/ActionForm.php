<?php

/**
 * @file
 * Contains \Drupal\rng\Form\ActionForm.
 */

namespace Drupal\rng\Form;

use Drupal\Core\Action\ActionManager;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for rng actions.
 */
class ActionForm extends ContentEntityForm {
  /**
   * The action entity.
   *
   * @var \Drupal\rng\ActionInterface
   */
  protected $entity;

  /**
   * The plugin entity.
   *
   * @todo: change when condition and action have a better common class.
   *
   * @var \Drupal\Core\Plugin\ContextAwarePluginBase
   */
  protected $plugin;

  /**
   * The action manager service.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $actionManager;

  /**
   * The condition manager service.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * Constructs a new action form.
   *
   * @param \Drupal\Core\Action\ActionManager $actionManager
   *   The action manager.
   * @param \Drupal\Core\Condition\ConditionManager $conditionManager
   *   The condition manager.
   */
  public function __construct(ActionManager $actionManager, ConditionManager $conditionManager) {
    $this->actionManager = $actionManager;
    $this->conditionManager = $conditionManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.action'),
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->entity->getConfiguration();
    $manager = $this->entity->getType() == 'condition' ? 'conditionManager' : 'actionManager';
    $this->plugin = $this->{$manager}->createInstance($this->entity->getPluginId(), $config);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $action = $this->entity;

    if (!$action->isNew()) {
      $form['#title'] = $this->t('Edit @type',
        array(
          '@type' => $action->getType(),
        )
      );
    }
    $form = $this->plugin->buildConfigurationForm($form, $form_state);
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
      drupal_set_message(t('Action created.', $t_args));
    }
    else {
      drupal_set_message(t('Action updated.', $t_args));
    }
  }

}
