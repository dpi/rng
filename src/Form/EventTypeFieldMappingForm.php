<?php

namespace Drupal\rng\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rng\EventManagerInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Form for event type field mapping.
 */
class EventTypeFieldMappingForm extends EntityForm {

  /**
   * Fields to add to event bundles.
   *
   * @var array
   */
  var $fields = [
    EventManagerInterface::FIELD_REGISTRATION_TYPE,
    EventManagerInterface::FIELD_REGISTRATION_GROUPS,
    EventManagerInterface::FIELD_STATUS,
    EventManagerInterface::FIELD_CAPACITY,
    EventManagerInterface::FIELD_EMAIL_REPLY_TO,
    EventManagerInterface::FIELD_ALLOW_DUPLICATE_REGISTRANTS,
    EventManagerInterface::FIELD_REGISTRATION_REGISTRANTS_MINIMUM,
    EventManagerInterface::FIELD_REGISTRATION_REGISTRANTS_MAXIMUM,
  ];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\rng\EventTypeInterface $event_type */
    $event_type = $this->entity;

    $form = parent::buildForm($form, $form_state);

    $form['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field name'),
        $this->t('Description'),
        $this->t('Field exists'),
        $this->t('Operations'),
      ],
    ];

    module_load_include('inc', 'rng', 'rng.field.defaults');
    foreach ($this->fields as $field_name) {
      $row = [];
      $definition = rng_event_field_config_definition($field_name);

      $row['field_name']['#plain_text'] = $definition['label'];
      $row['description']['#plain_text'] = isset($definition['description']) ? $definition['description'] : '';

      $exists = FieldConfig::loadByName($event_type->getEventEntityTypeId(), $event_type->getEventBundle(), $field_name);
      if ($exists) {
        $row['exists']['#plain_text'] = $this->t('Exists');
        $row['operations'][] = [];
      }
      else {
        $row['exists']['#plain_text'] = $this->t('Does not exist');
        $row['operations']['create'] = [
          '#name' => 'submit-create-' . $field_name,
          '#type' => 'submit',
          '#rng_field_name' => $field_name,
          '#value' => $this->t('Create'),
          '#submit' => [[static::class, 'createField']],
        ];
      }

      $form['table'][$field_name] = $row;
    }

    return $form;
  }

  /**
   * Form submission function to respond to the create field button.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function createField(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\rng\EventTypeInterface $event_type */
    $event_type = $form_state->getFormObject()->getEntity();

    $trigger = $form_state->getTriggeringElement();
    $field_name = $trigger['#rng_field_name'];
    $entity_type = $event_type->getEventEntityTypeId();
    $bundle = $event_type->getEventBundle();

    // Create the field.
    rng_add_event_field_storage($field_name, $entity_type);
    $field_config = rng_add_event_field_config($field_name, $entity_type, $bundle);
    drupal_set_message(t('Field %field_name added.', [
      '%field_name' => $field_config->label(),
    ]));

    // Make the field visible on the edit form.
    $display = entity_get_form_display($entity_type, $bundle, 'rng_event');
    rng_add_event_form_display_defaults($display, $field_name);
    $component = $display->getComponent($field_name);
    $component['weight'] = 9999;
    $display->setComponent($field_name, $component);
    $display->save();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   *
   * Remove delete element since it is confusing on non CRUD forms.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return [];
  }

}
