<?php

namespace Drupal\rng\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Set operation permissions for an entity.
 *
 * This action is for configuration only. It does not execute anything.
 *
 * @Action(
 *   id = "registration_operations",
 *   label = @Translation("Set registration operations"),
 *   type = "registration"
 * )
 */
class RegistrationAccess extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'operations' => [],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Always produce CRUD.
    $this->configuration['operations'] += [
      'create' => NULL,
      'view' => NULL,
      'update' => NULL,
      'delete' => NULL,
    ];

    $options = [];
    $values = [];
    foreach ($this->configuration['operations'] as $operation => $checked) {
      $options[$operation] = $operation;
      $values[$operation] = $checked ? $operation : NULL;
    }

    $form['operations'] = array(
      '#title' => $this->t('Operations'),
      '#description' => $this->t('Select which operations to grant.'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $values,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $operations = $form_state->getValue('operations');
    foreach ($operations as $op => $checked) {
      $this->configuration['operations'][$op] = $checked ? TRUE : NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute($context = NULL) {}

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {}

}
