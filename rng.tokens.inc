<?php

/**
 * @file
 * Builds tokens for RNG.
 */

use Drupal\rng\RegistrationInterface;

/**
 * Implements hook_token_info().
 */
function rng_token_info() {
  // Token types.
  $tokens['types']['registration'] = [
    'name' => t('Registrations'),
    'description' => t('Tokens for registrations.'),
    'needs-data' => 'registration',
  ];

  // Tokens.
  $tokens['tokens']['registration']['id'] = [
    'name' => t("ID"),
    'description' => t("Unique ID of the registration."),
  ];

  $tokens['tokens']['registration']['created'] = [
    'name' => t("Created"),
    'description' => t("The date the registration was created."),
    'type' => 'date',
  ];

  $tokens['tokens']['registration']['changed'] = [
    'name' => t("Updated"),
    'description' => t("The date the registration was last updated."),
    'type' => 'date',
  ];

  $tokens['tokens']['registration']['url'] = [
    'name' => t("URL"),
    'description' => t("The URL of the registration view page."),
    'type' => 'url',
  ];

  $tokens['tokens']['registration']['edit-url'] = [
    'name' => t("Edit URL"),
    'description' => t("The URL of the registration edit page."),
    'type' => 'url',
  ];

  return $tokens;
}

/**
 * Implements hook_tokens().
 */
function rng_tokens($type, $tokens, array $data = [], array $options = []) {
  $replacements = [];

  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }

  if ($type == 'registration' && !empty($data['registration'])) {
    if (($registration = $data['registration']) && $registration instanceof RegistrationInterface) {
      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'id':
            $replacements[$original] = $registration->id() ?: t('Unassigned');
            break;

          case 'created':
            $replacements[$original] = $registration->id() ? format_date($registration->getCreatedTime(), 'medium', '', NULL, $langcode) : t('Unassigned');
            break;

          case 'changed':
            $replacements[$original] = $registration->id() ? format_date($registration->getChangedTime(), 'medium', '', NULL, $langcode) : t('Unassigned');
            break;

          case 'url':
            $replacements[$original] = $registration->id() ? $registration->url('canonical', $url_options) : t('Unassigned');
            break;

          case 'edit-url':
            $replacements[$original] = $registration->id() ? $registration->url('edit-form', $url_options) : t('Unassigned');
            break;

        }
      }
    }
  }

  return $replacements;
}
