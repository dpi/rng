<?php

/**
 * @file
 * Creates field config if they do not exist.
 *
 * Call rng_add_event_field_config() directly.
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

function rng_add_event_field_storage($field_name, $entity_type) {
  if ($field_storage = FieldStorageConfig::loadByName($entity_type, $field_name)) {
    return NULL;
  }

  $definition = array();
  switch ($field_name) {
    case RNG_FIELD_EVENT_TYPE_REGISTRATION_TYPE:
      $definition = array(
        'type' => 'entity_reference',
        'settings' => array(
          'target_type' => 'registration_type',
        ),
      );
      break;
    case RNG_FIELD_EVENT_TYPE_STATUS:
      $definition = array(
        'type' => 'boolean',
        'settings' => array(
          'on_label' => t('Accepting new registrations'),
          'off_label' => t('Not accepting new registrations'),
        ),
      );
      break;
    case RNG_FIELD_EVENT_TYPE_CAPACITY:
      $definition = array(
        'type' => 'integer',
      );
      break;
    case RNG_FIELD_EVENT_TYPE_EMAIL_REPLY_TO:
      $definition = array(
        'type' => 'email',
      );
      break;
    case RNG_FIELD_EVENT_TYPE_ALLOW_DUPLICATE_REGISTRANTS:
      $definition = array(
        'type' => 'boolean',
        'settings' => array(
          'on_label' => t('Allow duplicate registrants for this event.'),
          'off_label' => t('Do not allow duplicate registrants for this event.'),
        ),
      );
      break;
  }

  if (!empty($definition)) {
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      ) + $definition
    );
    $field_storage->save();
  }
}

function rng_add_event_field_config($field_name, $entity_type, $bundle = NULL) {
  if ($field = FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
    return NULL;
  }

  $definition = array();
  switch ($field_name) {
    case RNG_FIELD_EVENT_TYPE_REGISTRATION_TYPE:
      $definition = array(
        'label' => 'Registration type',
        'settings' => array('handler' => 'default'),
        'description' => t('Select which registration types are valid for this event.'),
      );

      $form_display = entity_get_form_display($entity_type, $bundle, 'default')
        ->setComponent($field_name, array(
          'type' => 'entity_reference_autocomplete',
        ));
      break;
    case RNG_FIELD_EVENT_TYPE_STATUS:
      $definition = array(
        'label' => 'Accept new registrations',
      );

      $form_display = entity_get_form_display($entity_type, $bundle, 'default')
        ->setComponent($field_name, array(
          'type' => 'boolean_checkbox',
        ));
      break;
    case RNG_FIELD_EVENT_TYPE_CAPACITY:
      $definition = array(
        'label' => 'Maximum registrations',
        'description' => t('Maximum amount of registrations for this event. Leave empty for unlimited.'),
        'settings' => array(
          'min' => -1
        )
      );

      $form_display = entity_get_form_display($entity_type, $bundle, 'default')
        ->setComponent($field_name, array(
          'type' => 'number',
        ));
      break;
    case RNG_FIELD_EVENT_TYPE_EMAIL_REPLY_TO:
      $definition = array(
        'label' => t('Reply-to e-mail address'),
        'description' => t('E-mail address that appears as reply-to when emails are sent from this event. Leave empty to use site default.'),
      );

      $form_display = entity_get_form_display($entity_type, $bundle, 'default')
        ->setComponent($field_name, array(
          'type' => 'email_default',
          'settings' => array(
            'placeholder' => t('Leave empty to use site default.'),
          ),
        ));
      break;
    case RNG_FIELD_EVENT_TYPE_ALLOW_DUPLICATE_REGISTRANTS:
      $definition = array(
        'label' => 'Allow duplicate registrants',
        'description' => t('Allows a registrant to create more than one registration on this event.'),
      );

      $form_display = entity_get_form_display($entity_type, $bundle, 'default')
        ->setComponent($field_name, array(
          'type' => 'boolean_checkbox',
        ));
      break;
  }

  if (!empty($definition)) {
    $field = entity_create('field_config', array(
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
      ) + $definition
    );
    $field->save();

    if (isset($display)) {
      $display->save();
    }
    if (isset($form_display)) {
      $form_display->save();
    }
  }
}