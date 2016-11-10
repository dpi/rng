<?php

namespace Drupal\rng\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rng\EventManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Element;
use Drupal\user\Entity\Role;
use Drupal\Core\Session\AccountInterface;

/**
 * Configure condition plugin settings.
 */
class PluginConditionSettingsForm extends FormBase {

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new PluginConditionSettingsForm object.
   *
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(EventManagerInterface $event_manager) {
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('rng.event_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rng_plugin_condition_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $all_roles = Role::loadMultiple();
    unset($all_roles[AccountInterface::ANONYMOUS_ROLE]);
    unset($all_roles[AccountInterface::AUTHENTICATED_ROLE]);

    $roles = [];
    $values = [];
    foreach ($all_roles as $role) {
      /** @var \Drupal\user\RoleInterface $role */
      $roles[$role->id()] = $role->label();
      if ($role->getThirdPartySetting('rng', 'condition_rng_role', FALSE)) {
        $values[] = $role->id();
      }
    }

    $form['roles'] = [
      '#type' => 'checkboxes',
      '#options' => $roles,
      '#title' => $this->t('Roles'),
      '#description' => $this->t('Expose these roles to condition plugin. If no roles are selected, all roles will be made available.'),
      '#default_value' => $values,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('roles') as $role_id => $checked) {
      /** @var \Drupal\user\RoleInterface $role */
      $role = Role::load($role_id);
      $role
        ->setThirdPartySetting('rng', 'condition_rng_role', (boolean) $checked)
        ->save();
    }
    $this->eventManager->invalidateEventTypes();
    drupal_set_message(t('Updated condition plugin settings.'));
  }

}
