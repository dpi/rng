<?php

namespace Drupal\rng\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\user\Entity\User;
use Drupal\rng\RegistrantsElementUtility as RegistrantsElement;

/**
 * Provides a form element for a registrant and person association.
 *
 * Properties:
 * - #event: The associated event entity.
 *
 * Usage example:
 * @code
 * $form['registrants'] = [
 *   '#type' => 'registrants',
 *   '#event' => $event_entity,
 * ];
 * @endcode
 *
 * @FormElement("registrants")
 */
class Registrants extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processIdentityElement'],
      ],
      '#element_validate' => [
        [$class, 'validateIdentityElement'],
        [$class, 'validateRegisterable'],
        [$class, 'validateRegistrantCount'],
      ],
      '#pre_render' => array(
        array($class, 'preRenderRegistrants'),
      ),
      // Required.
      '#event' => NULL,
      '#attached' => [
        'library' => ['rng/rng.elements.registrants'],
      ],
      // Use container so classes are applied.
      '#theme_wrappers' => ['container'],
      // Allow creation of which entity types + bundles:
      //   Array of bundles keyed by entity type.
      '#allow_creation' => [],
      // Allow referencing existing entity types + bundles:
      //   Array of bundles keyed by entity type.
      '#allow_reference' => [],
      // Minimum number of registrants (integer), or NULL for no minimum.
      '#registrants_minimum' => NULL,
      // Maximum number of registrants (integer), or NULL for no maximum.
      '#registrants_maximum' => NULL,
      // Get form display modes used when creating entities inline.
      // An array in the format: [entity_type][bundle] = form_mode_id
      '#form_modes' => [],
    ];
  }

  /**
   * Process the registrant element.
   *
   * @param array $element
   *   An associative array containing the form structure of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   An associative array containing the structure of the form.
   *
   * @return array
   *   The new form structure for the element.
   */
  public static function processIdentityElement(array &$element, FormStateInterface $form_state, &$complete_form) {
    if (!isset($element['#event'])) {
      throw new \InvalidArgumentException('Element is missing #event property.');
    }
    if (!$element['#event'] instanceof EntityInterface) {
      throw new \InvalidArgumentException('#event for element is not an entity.');
    }
    if (empty($element['#allow_creation']) && empty($element['#allow_reference'])) {
      throw new \InvalidArgumentException('Element cannot create or reference any entities.');
    }

    $utility = new RegistrantsElement($element, $form_state);

    /** @var \Drupal\rng\RegistrantFactory $registrant_factory */
    $registrant_factory = \Drupal::service('rng.registrant.factory');
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    $entity_type_manager = \Drupal::entityTypeManager();

    $parents = $element['#parents'];

    $event = $element['#event'];

    $ajax_wrapper_id_root = 'ajax-wrapper-' . implode('-', $parents);

    $element['#tree'] = TRUE;
    $element['#identity_element_root'] = TRUE;
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id_root . '">';
    $element['#suffix'] = '</div>';

    /** @var \Drupal\rng\RegistrantInterface[] $people */
    $people = $element['#value'];

    $values = NestedArray::getValue($form_state->getUserInput(), $parents);
    $for_bundles = $utility->peopleTypeOptions();
    if (isset($values['entities']['for_bundle'])) {
      $for_bundle = $values['entities']['for_bundle'];
    }
    else {
      // Set for bundle if there is only one person type.
      $for_bundle = count($for_bundles) == 1 ? key($for_bundles) : NULL;
    }

    $arity_is_multiple = $utility->getArity() === 'multiple';
    $arity_is_single = !$arity_is_multiple;
    $change_it = $utility->getChangeIt();
    $entity_create_form = $utility->getShowCreateEntitySubform();

    if (!$change_it) {
      $element['for']['#tree'] = TRUE;
      if (count($people) > 0) {
        $people_labels = [];
        foreach ($people as $registrant) {
          $people_labels[] = (string) $registrant->getIdentity()->toLink()->toString();
        }

        if ($arity_is_single) {
          $people_labels = array_slice($people_labels, 0, 1);
        }

        $element['for']['fortext']['#markup'] = ((string) t('This registration is for')) . ' ' . implode(', ', $people_labels);

        $element['for']['change'] = [
          '#type' => 'submit',
          '#value' => t('Change'),
          '#ajax' => [
            'callback' => [static::class, 'ajaxElementRoot'],
            'wrapper' => $ajax_wrapper_id_root,
          ],
          '#limit_validation_errors' => [],
          '#validate' => [
            [static::class, 'decoyValidator'],
          ],
          '#submit' => [
            [static::class, 'submitChangeDefault'],
          ],
        ];
      }
      else {
        // There are zero registrants.
        $change_it = TRUE;
      }
    }

    $ajax_wrapper_id_people = 'ajax-wrapper-people-' . implode('-', $parents);

    // Drupals' radios element does not pass #executes_submit_callback and
    // #radios to its children radio like it does for #ajax. So we have to
    // create the children radios manually.
    $for_arity_default = $arity_is_multiple ? 'multiple' : 'single';
    $for_arity_options = [
      'single' => t('Single person'),
      'multiple' => t('Multiple people'),
    ];

    $for_arity_any_arity = TRUE;
    $minimum = $element['#registrants_minimum'];
    $maximum = $element['#registrants_maximum'];
    if (($minimum && $minimum > 1) || ($maximum && $maximum == 1)) {
      $for_arity_any_arity = FALSE;
    }

    $element['for_arity'] = [
      '#type' => 'radios',
      '#title' => t('This registration is for'),
      '#options' => NULL,
      '#access' => $for_arity_any_arity && $change_it,
      '#attributes' => [
        'class' => ['for_arity'],
      ],
    ];
    foreach ($for_arity_options as $key => $label) {
      $element['for_arity'][$key]['radio'] = [
        '#type' => 'radio',
        '#title' => $label,
        '#return_value' => $key,
        '#default_value' => $key === $for_arity_default,
        '#parents' => array_merge($parents, ['for_arity']),
        '#ajax' => [
          'callback' => [static::class, 'ajaxElementRoot'],
          'wrapper' => $ajax_wrapper_id_root,
          'progress' => [
            'type' => 'throbber',
            'message' => NULL,
          ],
        ],
        '#limit_validation_errors' => [],
        '#validate' => [
          [static::class, 'decoyValidator'],
        ],
        '#executes_submit_callback' => TRUE,
        '#submit' => [
          [static::class, 'submitArityChange'],
        ],
        '#disabled' => count($people) > 1,
      ];
    }

    $element['people'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id_people . '">',
      '#suffix' => '</div>',
    ];
    $element['people']['people_list'] = [
      '#type' => 'table',
      '#header' => [
        t('Person'), t('Operations'),
      ],
      '#access' => $arity_is_multiple && $change_it,
      '#empty' => t('There are no people yet, add people below.'),
    ];

    foreach ($people as $i => $registrant) {
      $row = [];
      $row[]['#markup'] = $registrant->getIdentity()->toLink()->toString();

      $row[] = [
        // Needs a name else the submission handlers think all buttons are the
        // last button.
        '#name' => 'ajax-submit-' . implode('-', $parents) . '-' . $i,
        '#type' => 'submit',
        '#value' => t('Remove'),
        '#ajax' => [
          'callback' => [static::class, 'ajaxElementRoot'],
          'wrapper' => $ajax_wrapper_id_root,
        ],
        '#limit_validation_errors' => [],
        '#validate' => [
          [static::class, 'decoyValidator'],
        ],
        '#submit' => [
          [static::class, 'submitRemovePerson'],
        ],
        '#identity_element_registrant_row' => $i,
      ];

      $element['people']['people_list'][] = $row;
    }

    $ajax_wrapper_id_entities = 'ajax-wrapper-entities-' . implode('-', $parents);

    $element['entities'] = [
      '#type' => 'details',
      '#access' => $change_it,
      '#prefix' => '<div id="' . $ajax_wrapper_id_entities . '">',
      '#suffix' => '</div>',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $arity_is_multiple ? t('Add another person') : t('Select person'),
      '#attributes' => [
        'class' => ['entities'],
      ],
    ];

    $element['entities']['controls'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => [
        'class' => ['person-controls'],
      ],
    ];

    $element['entities']['controls']['for_bundle'] = [
      '#type' => 'radios',
      '#title' => t('Person type'),
      '#options' => $for_bundles,
      '#default_value' => $for_bundle,
      '#access' => $change_it,
      '#ajax' => [
        'callback' => [static::class, 'ajaxElementRoot'],
        'wrapper' => $ajax_wrapper_id_root,
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
      '#validate' => [
        [static::class, 'decoyValidator'],
      ],
      '#attributes' => [
        'class' => ['person-type'],
      ],
      '#parents' => array_merge($parents, ['entities', 'for_bundle']),
    ];

    $element['entities']['controls']['actions'] = [
      '#type' => 'actions',
      '#tree' => TRUE,
    ];

    // Display a close button if there are people and arity is multiple.
    if ($arity_is_multiple && count($people) > 0) {
      $element['entities']['controls']['actions']['done'] = [
        '#type' => 'submit',
        '#value' => t('Done'),
        '#ajax' => [
          'callback' => [static::class, 'ajaxElementRoot'],
          'wrapper' => $ajax_wrapper_id_root,
        ],
        '#limit_validation_errors' => [],
        '#validate' => [
          [static::class, 'decoyValidator'],
        ],
        '#submit' => [
          [static::class, 'submitClose'],
        ],
      ];
    }

    $element['entities']['person'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => [
        'class' => ['person-container'],
      ],
    ];
    $person_subform = &$element['entities']['person'];

    if ($change_it && isset($for_bundle)) {
      list($person_entity_type_id, $person_bundle) = explode(':', $for_bundle);

      // Registrant
      $person_subform['registrant'] = [
        '#tree' => TRUE,
        '#open' => TRUE,
        '#title' => t('Registrant metadata'),
        '#parents' => array_merge($parents, ['entities', 'person', 'registrant']),
      ];

      unset($registrant);
      if ($arity_is_single) {
        $first_registrant = reset($people);
        if ($first_registrant) {
          $registrant = $first_registrant;
        }
      }
      // If no first registrant, then create one.
      if (!isset($registrant)) {
        $registrant = $registrant_factory->createRegistrant([
          'event' => $event,
        ]);
      }

      $display = entity_get_form_display('registrant', $registrant->bundle(), 'default');
      $display->buildForm($registrant, $person_subform['registrant'], $form_state);
      $form_state->set('registrant__form_display', $display);
      $form_state->set('registrant__entity', $registrant);

      if ($for_bundle === 'myself:') {
        $person_subform['myself']['actions'] = [
          '#type' => 'actions',
        ];
        $person_subform['myself']['actions']['add_myself'] = [
          '#type' => 'submit',
          '#value' => $arity_is_single ? t('Select my account') : t('Add my account'),
          '#ajax' => [
            'callback' => [static::class, 'ajaxElementRoot'],
            'wrapper' => $ajax_wrapper_id_root,
          ],
          '#limit_validation_errors' => [
            array_merge($element['#parents'], ['entities', 'person', 'registrant']),
            array_merge($element['#parents'], ['entities', 'person', 'myself'])
          ],
          '#validate' => [
            [static::class, 'validateMyself'],
          ],
          '#submit' => [
            [static::class, 'submitMyself'],
          ],
        ];
      }
      else {
        $entity_type = $entity_type_manager->getDefinition($person_entity_type_id);
        $entity_bundle_info = $bundle_info->getBundleInfo($person_entity_type_id);
        $bundle_info = $entity_bundle_info[$person_bundle];

        $allow_reference = isset($element['#allow_reference'][$person_entity_type_id]) && in_array($person_bundle, $element['#allow_reference'][$person_entity_type_id]);

        // Existing person
        $person_subform['existing'] = [
          '#type' => 'details',
          '#open' => TRUE,
          '#title' => t('Existing @entity_type', ['@entity_type' => $entity_type->getLabel()]),
          '#identity_element_existing_container' => TRUE,
          '#attributes' => [
            'class' => ['existing-container'],
          ],
          '#access' => $allow_reference && $utility->countReferenceableEntities($event, $person_entity_type_id) > 0,
        ];
        $person_subform['existing']['existing_autocomplete'] = [
          '#type' => 'entity_autocomplete',
          '#title' => t('Existing @entity_type', ['@entity_type' => $entity_type->getLabel()]),
          '#target_type' => $person_entity_type_id,
          '#tags' => FALSE,
          '#selection_handler' => 'rng_register',
          '#selection_settings' => [
            'event_entity_type' => $event->getEntityTypeId(),
            'event_entity_id' => $event->id(),
          ],
          '#wrapper_attributes' => [
            'class' => ['existing-autocomplete-container'],
          ],
        ];

        if ($entity_type->getBundleEntityType() !== NULL) {
          // This entity type has bundles.
          $person_subform['existing']['existing_autocomplete']['#selection_settings']['target_bundles'] = [$person_bundle];
        }

        if ($arity_is_single) {
          $first_registrant = reset($people);
          if ($first_registrant) {
            $identity = $first_registrant->getIdentity();
            if (isset($identity) && ($identity->getEntityTypeId() == $person_entity_type_id)) {
              $person_subform['existing']['existing_autocomplete']['#default_value'] = $identity;
            }
          }
        }

        $person_subform['existing']['actions'] = [
          '#type' => 'actions',
        ];
        $person_subform['existing']['actions']['add_existing'] = [
          '#type' => 'submit',
          '#value' => $arity_is_single ? t('Select person') : t('Add person'),
          '#ajax' => [
            'callback' => [static::class, 'ajaxElementRoot'],
            'wrapper' => $ajax_wrapper_id_root,
          ],
          '#limit_validation_errors' => [
            array_merge($element['#parents'], ['entities', 'person', 'registrant']),
            array_merge($element['#parents'], ['entities', 'person', 'existing'])
          ],
          '#validate' => [
            [static::class, 'validateExisting'],
          ],
          '#submit' => [
            [static::class, 'submitExisting'],
          ],
        ];

        // New entity
        $create = FALSE;
        if (isset($element['#allow_creation'][$person_entity_type_id])) {
          $create = RegistrantsElement::entityCreateAccess($person_entity_type_id, $person_bundle);
        }
        $person_subform['new_person'] = [
          '#type' => 'details',
          '#open' => TRUE,
          '#tree' => TRUE,
          '#title' => t('New @entity_type', ['@entity_type' => $entity_type->getLabel()]),
          '#identity_element_create_container' => TRUE,
          '#access' => $create,
        ];

        if ($entity_create_form) {
          $person_subform['new_person']['newentityform'] = [
            '#access' => $entity_create_form,
            '#tree' => TRUE,
            '#parents' => array_merge($parents, ['entities', 'person', 'new_person', 'newentityform']),
          ];

          $entity_storage = $entity_type_manager->getStorage($person_entity_type_id);
          $new_person_options = [];
          if ($entity_type->getBundleEntityType() !== NULL) {
            // This entity type has bundles.
            $new_person_options[$entity_type->getKey('bundle')] = $person_bundle;
          }
          $new_person = $entity_storage->create($new_person_options);

          $form_mode = 'default';
          if (isset($element['#form_modes'][$person_entity_type_id][$person_bundle])) {
            $form_mode = $element['#form_modes'][$person_entity_type_id][$person_bundle];
          }

          $display = entity_get_form_display($person_entity_type_id, $person_bundle, $form_mode);
          $display->buildForm($new_person, $person_subform['new_person']['newentityform'], $form_state);
          $form_state->set('newentity__form_display', $display);
          $form_state->set('newentity__entity', $new_person);

          $person_subform['new_person']['actions'] = [
            '#type' => 'actions',
            '#weight' => 10000,
          ];

          $person_subform['new_person']['actions']['create'] = [
            '#type' => 'submit',
            '#value' => $arity_is_single ? t('Create and select person') : t('Create and add to registration'),
            '#ajax' => [
              'callback' => [static::class, 'ajaxElementRoot'],
              'wrapper' => $ajax_wrapper_id_root,
            ],
            '#limit_validation_errors' => [
              array_merge($parents, ['entities', 'person', 'registrant']),
              array_merge($parents, ['entities', 'person', 'new_person'])
            ],
            '#validate' => [
              [static::class, 'validateCreate'],
            ],
            '#submit' => [
              [static::class, 'submitCreate'],
            ],
          ];

          $person_subform['new_person']['actions']['cancel'] = [
            '#type' => 'submit',
            '#value' => t('Cancel'),
            '#ajax' => [
              'callback' => [static::class, 'ajaxElementRoot'],
              'wrapper' => $ajax_wrapper_id_root,
            ],
            '#limit_validation_errors' => [],
            '#toggle_create_entity' => FALSE,
            '#validate' => [
              [static::class, 'decoyValidator'],
            ],
            '#submit' => [
              [static::class, 'submitToggleCreateEntity'],
            ],
          ];
        }
        else {
          $person_subform['new_person']['load_create_form'] = [
            '#type' => 'submit',
            '#value' => t('Create new @label', ['@label' => $bundle_info['label']]),
            '#ajax' => [
              'callback' => [static::class, 'ajaxElementRoot'],
              'wrapper' => $ajax_wrapper_id_root,
            ],
            '#validate' => [
              [static::class, 'decoyValidator'],
            ],
            '#submit' => [
              [static::class, 'submitToggleCreateEntity'],
            ],
            '#toggle_create_entity' => TRUE,
            '#limit_validation_errors' => [],
          ];
        }
      }
    }
    else {
      // There is no subform displayed to the side of "Person type" radios:
      $person_subform['#attributes']['class'][] = 'empty';

      $person_subform['select-person-type'] = [
        '#plain_text' => t('Select person type'),
        '#prefix' => '<div class="message">',
        '#suffix' => '</div>',
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $parents = array_merge($element['#parents'], ['registrants']);
    $value = $form_state->get($parents);

    if ($value === NULL) {
      return isset($element['#default_value']) ? $element['#default_value'] : [];
    }

    return $value;
  }

  /**
   * An empty form validator.
   *
   * This validator is used to prevent top level form validators from running.
   * Submission elements must have a dummy validator, not just an empty
   * #validate property.
   *
   * See \Drupal\Core\Form\FormValidator::executeValidateHandlers for the
   * critical core operation details.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function decoyValidator(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Generic validator for the element.
   */
  public static function validateIdentityElement(&$element, FormStateInterface $form_state, &$complete_form) {
    $utility = new RegistrantsElement($element, $form_state);

    $registrants = $element['#value'];

    $arity_is_single = $utility->getArity() === 'single';
    if ($arity_is_single) {
      $registrants = array_slice($registrants, 0, 1);
      $change_it = $utility->getChangeIt();
      if ($change_it) {
        // Ensure if the change it is TRUE and single form is open then throw
        // error.
        $form_state->setError($element, t('You must select a person.'));
      }
    }

    // Store original form submission in temporary values.
    $values = $form_state->getValue($element['#parents']);
    $form_state->setTemporaryValue(array_merge(['_registrants_values'], $element['#parents']), $values);

    // Change element value to registrant entities.
    $form_state->setValueForElement($element, $registrants);
  }

  /**
   * Validate whether all existing registrants are register-able.
   *
   * An identity may have been registered by another registration while
   * it is also stored in the state of another registration.
   */
  public static function validateRegisterable(&$element, FormStateInterface $form_state, &$complete_form) {
    $utility = new RegistrantsElement($element, $form_state);

    /** @var \Drupal\rng\RegistrantInterface[] $registrants */
    $registrants = $element['#value'];
    $whitelisted = $utility->getWhitelistExisting();

    $identities = [];
    foreach ($registrants as $registrant) {
      $identity = $registrant->getIdentity();
      $entity_type = $identity->getEntityTypeId();
      $id = $identity->id();
      // Check if identity can skip existing revalidation. This needs to be done
      // when the identity was created by this element.
      if (!isset($whitelisted[$entity_type][$id])) {
        $identities[$entity_type][$id] = $identity->label();
      }
    }

    /** @var \Drupal\rng\EventManagerInterface $event_manager */
    $event_manager = \Drupal::service('rng.event_manager');
    $event = $element['#event'];
    $event_meta = $event_manager->getMeta($event);
    foreach ($identities as $entity_type => $identity_labels) {
      $registerable = $event_meta->identitiesCanRegister($entity_type, array_keys($identity_labels));
      // Flip identity entity IDs to array keys.
      $registerable = array_flip($registerable);
      foreach (array_diff_key($identities[$entity_type], $registerable) as $id => $label) {
        $form_state->setError($element, t('%name cannot register for this event.', [
          '%name' => $label,
        ]));
      }
    }
  }

  /**
   * Validate whether there are sufficient quantity of registrants.
   */
  public static function validateRegistrantCount(&$element, FormStateInterface $form_state, &$complete_form) {
    /** @var \Drupal\rng\RegistrantInterface[] $registrants */
    $registrants = $element['#value'];
    $count = count($registrants);

    if (isset($element['#registrants_minimum'])) {
      $minimum = $element['#registrants_minimum'];
      if ($count < $minimum) {
        $form_state->setError($element, t('There are not enough registrants on this registration. There must be at least @minimum registrants.', [
          '@minimum' => $minimum,
        ]));
      }
    }

    if (isset($element['#registrants_maximum'])) {
      $maximum = $element['#registrants_maximum'];
      if ($count > $maximum) {
        $form_state->setError($element, t('There are too many registrants on this registration. There must be at most @maximum registrants.', [
          '@maximum' => $maximum,
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderRegistrants($element) {
    $element['#attributes']['class'][] = 'registrants-element';
    return $element;
  }

  /**
   * Ajax callback to return the entire element.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The entire element sub-form.
   */
  public static function ajaxElementRoot(array $form, FormStateInterface $form_state) {
    return RegistrantsElement::findElement($form, $form_state);
  }

  /**
   * Validate adding myself sub-form.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateMyself(array &$form, FormStateInterface $form_state) {
    $element = RegistrantsElement::findElement($form, $form_state);
    $utility = new RegistrantsElement($element, $form_state);
    $utility->buildRegistrant(TRUE);
  }

  /**
   * Validate adding existing entity sub-form.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateExisting(array &$form, FormStateInterface $form_state) {
    $element = RegistrantsElement::findElement($form, $form_state);
    $utility = new RegistrantsElement($element, $form_state);

    $utility->buildRegistrant(TRUE);

    $autocomplete_tree = array_merge($element['#parents'], ['entities', 'person', 'existing', 'existing_autocomplete']);

    $element_existing = NestedArray::getValue($element, ['entities', 'person', 'existing', 'existing_autocomplete']);
    $existing_entity_type = $element_existing['#target_type'];
    $existing_value = NestedArray::getValue($form_state->getTemporaryValue('_registrants_values'), $autocomplete_tree);

    if (!empty($existing_value)) {
      $new_arity = $utility->getArity();
      if ($new_arity === 'multiple') {
        $identity = \Drupal::entityTypeManager()->getStorage($existing_entity_type)
          ->load($existing_value);
        if ($utility->identityExists($identity)) {
          $form_state->setError(NestedArray::getValue($form, $autocomplete_tree), t('Person is already on this registration.'));
        }
      }
    }
    else {
      $form_state->setError(NestedArray::getValue($form, $autocomplete_tree), t('Choose a person.'));
    }
  }

  /**
   * Validate identity creation sub-form.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateCreate(array &$form, FormStateInterface $form_state) {
    $element = RegistrantsElement::findElement($form, $form_state);
    $utility = new RegistrantsElement($element, $form_state);

    $utility->buildRegistrant(TRUE);

    $new_person_tree = array_merge($element['#parents'], ['entities', 'person', 'new_person', 'newentityform']);
    $subform_newentity = NestedArray::getValue($form, $new_person_tree);

    $value = $form_state->getTemporaryValue(array_merge(['_registrants_values'], $element['#parents']));
    $form_state->setValue($element['#parents'], $value);

    $new_person = $form_state->get('newentity__entity');
    $form_display = $form_state->get('newentity__form_display');
    $form_display->extractFormValues($new_person, $subform_newentity, $form_state);
    $form_display->validateFormValues($new_person, $subform_newentity, $form_state);

    /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $violations */
    $violations = $new_person->validate();
    if ($violations->count() == 0) {
      $form_state->set('newentity__entity', $new_person);
    }
    else {
      $triggering_element = $form_state->getTriggeringElement();
      foreach ($violations as $violation) {
        $form_state->setError($triggering_element, (string) $violation->getMessage());
      }
    }
  }

  /**
   * Submission callback to change the registrant from the default people.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitChangeDefault(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();

    $element = RegistrantsElement::findElement($form, $form_state);
    $utility = new RegistrantsElement($element, $form_state);

    $utility->setChangeIt(TRUE);

    $new_arity = $utility->getArity();
    if ($new_arity === 'single') {
      $utility->clearPeopleFormInput();
      $utility->setForBundleAsFirstRegistrant();
    }
  }

  /**
   * Submission callback to close the selection interface.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitClose(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();

    $element = RegistrantsElement::findElement($form, $form_state);
    $utility = new RegistrantsElement($element, $form_state);

    $utility->setChangeIt(FALSE);
    $utility->clearPeopleFormInput();
  }

  /**
   * For_arity radios submission handler.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitArityChange(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    $trigger = $form_state->getTriggeringElement();
    $element = RegistrantsElement::findElement($form, $form_state);
    $utility = new RegistrantsElement($element, $form_state);

    /** @var \Drupal\rng\RegistrantInterface[] $people */
    $people = $element['#value'];

    $new_arity = $trigger['#value'];
    $utility->setArity($new_arity);

    if ((count($people) > 0)) {
      if ($new_arity === 'single') {
        $utility->clearPeopleFormInput();
        $utility->setForBundleAsFirstRegistrant();
      }
      else {
        $utility->clearPeopleFormInput();
        $parents = array_merge($element['#parents'], ['entities', 'for_bundle']);
        NestedArray::unsetValue($form_state->getUserInput(), $parents);
      }
    }
  }

  /**
   * Submission callback for referencing the current user.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitMyself(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();

    $element = RegistrantsElement::findElement($form, $form_state);
    $utility = new RegistrantsElement($element, $form_state);

    $registrant = $utility->buildRegistrant();
    $utility->clearPeopleFormInput();

    $current_user = \Drupal::currentUser();
    if ($current_user->isAuthenticated()) {
      $person = User::load($current_user->id());
      $registrant->setIdentity($person);
    }

    $arity = $utility->getArity();
    if ($arity === 'single') {
      $utility->replaceFirstRegistrant($registrant);
      $utility->setChangeIt(FALSE);
    }
    else {
      $utility->addRegistrant($registrant);
    }
  }

  /**
   * Submission callback for existing entities.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitExisting(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();

    $element = RegistrantsElement::findElement($form, $form_state);
    $utility = new RegistrantsElement($element, $form_state);

    $registrant = $utility->buildRegistrant();
    $utility->clearPeopleFormInput();

    $autocomplete_tree = array_merge($element['#parents'], ['entities', 'person', 'existing', 'existing_autocomplete']);
    $existing_value = NestedArray::getValue($form_state->getTemporaryValue('_registrants_values'), $autocomplete_tree);

    $subform_autocomplete = NestedArray::getValue($form, $autocomplete_tree);
    $existing_entity_type = $subform_autocomplete['#target_type'];
    $person = \Drupal::entityTypeManager()->getStorage($existing_entity_type)
      ->load($existing_value);
    $registrant->setIdentity($person);

    $arity = $utility->getArity();
    if ($arity === 'single') {
      $utility->replaceFirstRegistrant($registrant);
      $utility->setChangeIt(FALSE);
    }
    else {
      $utility->addRegistrant($registrant);
    }
  }

  /**
   * Submission callback for creating new entities.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitCreate(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();

    $element = RegistrantsElement::findElement($form, $form_state);
    $utility = new RegistrantsElement($element, $form_state);

    // New entity
    $new_entity_tree = array_merge($element['#parents'], ['entities', 'person', 'new_person', 'newentityform']);
    $subform_new_entity = NestedArray::getValue($form, $new_entity_tree);

    // Save the entity.
    /** @var \Drupal\Core\Entity\EntityInterface $new_person */
    $new_person = $form_state->get('newentity__entity');
    $display = $form_state->get('newentity__form_display');

    $value = $form_state->getTemporaryValue(array_merge(['_registrants_values'], $element['#parents']));
    $form_state->setValue($element['#parents'], $value);
    $display->extractFormValues($new_person, $subform_new_entity, $form_state);
    $new_person->save();
    $utility->addWhitelistExisting($new_person);

    $registrant = $utility->buildRegistrant();
    $utility->clearPeopleFormInput();

    $registrant->setIdentity($new_person);

    $arity = $utility->getArity();
    if ($arity === 'single') {
      $utility->replaceFirstRegistrant($registrant);
      $utility->setChangeIt(FALSE);
      $utility->setShowCreateEntitySubform(FALSE);
    }
    else {
      $utility->addRegistrant($registrant);
      $utility->setShowCreateEntitySubform(FALSE);
    }
  }

  /**
   * Submission callback for toggling the create sub-form.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitToggleCreateEntity(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $form_state->setRebuild();

    $element = RegistrantsElement::findElement($form, $form_state);
    $utility = new RegistrantsElement($element, $form_state);
    $utility->setShowCreateEntitySubform($trigger['#toggle_create_entity']);
  }

  /**
   * Submission callback for removing a registrant.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitRemovePerson(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();

    $element = RegistrantsElement::findElement($form, $form_state);
    $utility = new RegistrantsElement($element, $form_state);

    $trigger = $form_state->getTriggeringElement();
    $row = $trigger['#identity_element_registrant_row'];

    $registrants = $utility->getRegistrants();
    unset($registrants[$row]);
    $utility->setRegistrants($registrants);
  }

}
