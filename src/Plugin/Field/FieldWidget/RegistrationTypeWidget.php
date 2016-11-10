<?php

namespace Drupal\rng\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;

/**
 * Widget for `registration type` entity reference fields.
 *
 * @FieldWidget(
 *   id = "rng_registration_type",
 *   label = @Translation("Registration type checkboxes"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class RegistrationTypeWidget extends OptionsButtonsWidget {
  // @todo Stub, to be expanded later. See: https://github.com/dpi/rng/issues/62
}
