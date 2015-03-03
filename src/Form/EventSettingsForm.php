<?php

/**
 * @file
 * Contains \Drupal\rng\Form\EventSettingsForm.
 */

namespace Drupal\rng\Form;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Configure event settings.
 */
class EventSettingsForm extends FormBase {
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
      RNG_FIELD_EVENT_TYPE_STATUS,
      RNG_FIELD_EVENT_TYPE_ALLOW_DUPLICATE_REGISTRANTS,
      RNG_FIELD_EVENT_TYPE_CAPACITY,
      RNG_FIELD_EVENT_TYPE_EMAIL_REPLY_TO,
      RNG_FIELD_EVENT_TYPE_REGISTRATION_TYPE,
      RNG_FIELD_EVENT_TYPE_REGISTRATION_GROUPS,
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

    $t_args = array('%event_label' => $event->label());
    drupal_set_message(t('Event settings updated.', $t_args));
  }
}