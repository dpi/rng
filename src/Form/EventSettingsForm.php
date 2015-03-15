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
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new MessageActionForm object.
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
      $this->eventManager->getMeta($event)->addDefaultAccess();
    }

    $t_args = array('%event_label' => $event->label());
    drupal_set_message(t('Event settings updated.', $t_args));
  }
}