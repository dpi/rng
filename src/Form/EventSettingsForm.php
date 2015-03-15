<?php

/**
 * @file
 * Contains \Drupal\rng\Form\EventSettingsForm.
 */

namespace Drupal\rng\Form;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\rng\EventManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Configure event settings.
 */
class EventSettingsForm extends FormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new MessageActionForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param EventManager $event_manager
   *   The RNG event manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, EventManagerInterface $event_manager) {
    $this->entityManager = $entity_manager;
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('rng.event_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rng_event_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RouteMatchInterface $route_match = NULL, $event = NULL) {
    $entity = clone $route_match->getParameter($event);
    $form_state->set('event', $entity);

    $fields = array(
      EventManagerInterface::FIELD_STATUS,
      EventManagerInterface::FIELD_ALLOW_DUPLICATE_REGISTRANTS,
      EventManagerInterface::FIELD_CAPACITY,
      EventManagerInterface::FIELD_EMAIL_REPLY_TO,
      EventManagerInterface::FIELD_REGISTRATION_TYPE,
      EventManagerInterface::FIELD_REGISTRATION_GROUPS,
    );

    $display = EntityFormDisplay::collectRenderDisplay($entity, 'default');
    $form_state->set('form_display', $display);
    module_load_include('inc', 'rng', 'rng.field.defaults');

    $components = array_keys($display->getComponents());
    foreach ($components as $field_name) {
      if (!in_array($field_name, $fields)) {
        $display->removeComponent($field_name);
      }
    }

    // Add widget settings if field is hidden on default view.
    foreach ($fields as $field_name) {
      if (!in_array($field_name, $components)) {
        rng_add_event_form_display_defaults($display, $field_name);
      }
    }

    $form['event'] = array(
      '#weight' => 0,
    );

    $form['advanced'] = array(
      '#type' => 'vertical_tabs',
      '#weight' => 100,
    );

    $display->buildForm($entity, $form['event'], $form_state);

    foreach ($fields as $weight => $field_name) {
      $form['event'][$field_name]['#weight'] = $weight * 10;
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $event = $form_state->get('event');
    $form_state->get('form_display')->extractFormValues($event, $form, $form_state);
    $event->save();

    // Create base register rules if none exist.

    $query = $this->eventManager->getMeta($event)->buildRuleQuery();
    $rule_count = $query->condition('trigger_id', 'rng_event.register', '=')->count()->execute();
    if (!$rule_count) {
      // Allow any user to create a registration on the event.
      $rules['user_role']['conditions']['rng_user_role'] = ['roles' => ['authenticated' => 'authenticated']];
      $rules['user_role']['actions']['registration_operations'] = ['operations' => ['create' => TRUE]];

      // Allow registrants to edit their registrations.
      $rules['registrant']['conditions']['rng_registration_identity'] = [];
      $rules['registrant']['actions']['registration_operations'] = ['operations' => ['view' => TRUE, 'update' => TRUE]];

      // Give event managers all rights.
      $rules['event_operation']['conditions']['rng_event_operation'] = ['operations' => ['manage event' => TRUE]];
      $rules['event_operation']['actions']['registration_operations'] = ['operations' => ['create' => TRUE, 'view' => TRUE, 'update' => TRUE, 'delete' => TRUE]];

      foreach ($rules as $rule) {
        $rng_rule = $this->entityManager->getStorage('rng_rule')->create(array(
          'event' => array('entity' => $event),
          'trigger_id' => 'rng_event.register',
        ));
        $rng_rule->save();
        foreach ($rule['conditions'] as $plugin_id => $configuration) {
          $this->entityManager->getStorage('rng_action')->create([])
            ->setRule($rng_rule)
            ->setType('condition')
            ->setPluginId($plugin_id)
            ->setConfiguration($configuration)
            ->save();
        }
        foreach ($rule['actions'] as $plugin_id => $configuration) {
          $this->entityManager->getStorage('rng_action')->create([])
            ->setRule($rng_rule)
            ->setType('action')
            ->setPluginId($plugin_id)
            ->setConfiguration($configuration)
            ->save();
        }
      }
    }

    $t_args = array('%event_label' => $event->label());
    drupal_set_message(t('Event settings updated.', $t_args));
  }
}