<?php

namespace Drupal\rng\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;

/**
 * Widget for `registration group` entity reference fields.
 *
 * @FieldWidget(
 *   id = "rng_registration_group",
 *   label = @Translation("Registration group checkboxes"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class RegistrationGroupWidget extends OptionsButtonsWidget {
  // @todo Stub, to be expanded later. See: https://github.com/dpi/rng/issues/62
}
