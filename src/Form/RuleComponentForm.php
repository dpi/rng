<?php

namespace Drupal\rng\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Action\ActionManager;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;

/**
 * Form controller for rng rule components.
 */
class RuleComponentForm extends ContentEntityForm {

  /**
   * The action entity.
   *
   * @var \Drupal\rng\RuleComponentInterface
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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Action\ActionManager $action_manager
   *   The action manager.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, ActionManager $action_manager, ConditionManager $condition_manager) {
    parent::__construct($entity_manager);
    $this->actionManager = $action_manager;
    $this->conditionManager = $condition_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
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

    $event = $this->entity->getRule()->getEvent();
    // Reset tags for event. Forces re-render of things like tabs.
    Cache::invalidateTags($event->getCacheTagsToInvalidate());

    $this->plugin->submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\rng\RuleComponentInterface $component */
    $component = $this->getEntity();
    $is_new = $component->isNew();
    $plugin_configuration = $this->plugin->getConfiguration();

    $component->setConfiguration($plugin_configuration);
    $component->save();

    $type = $this->entity->getType();
    $types = ['action' => $this->t('Action'), 'condition' => $this->t('Condition')];
    $t_args = ['@type' => isset($types[$type]) ? $types[$type] : $this->t('Component')];

    if ($is_new) {
      drupal_set_message(t('@type created.', $t_args));
    }
    else {
      drupal_set_message(t('@type updated.', $t_args));
    }
  }

}
