<?php

namespace Drupal\rng\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\user\Entity\User;
use Drupal\rng\Entity\Registrant;
use Drupal\rng\RegistrantInterface;

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
      // Allow creation of which entity types.
      '#allow_creation' => [],
      // Allow multiple registrants.
      '#registrants_maximum' => NULL,
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

    /** @var \Drupal\rng\EventManagerInterface $event_manager */
    $event_manager = \Drupal::service('rng.event_manager');
    $entity_type_manager = \Drupal::entityTypeManager();
    $current_user = \Drupal::currentUser();

    $parents = $element['#parents'];

    $event = $element['#event'];
    $event_meta = $event_manager->getMeta($event);

    $ajax_wrapper_id_root = 'ajax-wrapper-' . implode('-', $parents);

    $element['#tree'] = TRUE;
    $element['#identity_element_root'] = TRUE;
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id_root . '">';
    $element['#suffix'] = '</div>';

    /** @var \Drupal\rng\RegistrantInterface[] $people */
    $people = $element['#value'];

    $values = NestedArray::getValue($form_state->getUserInput(), $parents);
    $for_bundle = isset($values['entities']['for_bundle']) ? $values['entities']['for_bundle'] : NULL;

    $arity_is_multiple = static::getArity($element, $form_state) === 'multiple';
    $arity_is_single = !$arity_is_multiple;
    $change_it = static::getChangeIt($element, $form_state);
    $entity_create_form = static::getShowCreateEntitySubform($element, $form_state);

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

    $max_access = !isset($element['#registrants_maximum']) || ($element['#registrants_maximum'] > 1);
    $element['for_arity'] = [
      '#type' => 'radios',
      '#title' => t('This registration is for'),
      '#options' => NULL,
      '#access' => $change_it && $max_access,
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
        '#executes_submit_callback' => TRUE,
        '#submit' => [
          [static::class, 'submitArityChange'],
        ],
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
        '#submit' => [
          [static::class, 'submitRemovePerson'],
        ],
        '#identity_element_registrant_row' => $i,
      ];

      $element['people']['people_list'][] = $row;
    }

    $for_bundles = [];
    foreach ($event_meta->getIdentityTypes() as $entity_type_id) {
      $create = FALSE;
      if (isset($element['#allow_creation'][$entity_type_id])) {
        $create = $entity_type_manager->getAccessControlHandler($entity_type_id)
          ->createAccess();
      }

      $existing_count = static::countReferenceableEntities($event, $entity_type_id) > 0;
      if ($entity_type_id === 'user' && $current_user->isAuthenticated()) {
        $identity = User::load($current_user->id());
        if (!static::identityExists($element, $form_state, $identity)) {
          if ($event_meta->identitiesCanRegister('user', [$current_user->id()])) {
            $existing_count--;
            $for_bundles['myself:'] = t('Myself');
          }
        }
      }

      if ($create || ($existing_count > 0)) {
        $entity_type = $entity_type_manager->getDefinition($entity_type_id);
        $for_bundles[$entity_type_id . ':' . $entity_type_id] = $entity_type->getLabel();
      }
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

    $element['entities']['for_bundle'] = [
      '#type' => 'radios',
      '#title' => t('Person type'),
      '#options' => $for_bundles,
      '#access' => $change_it,
      '#ajax' => [
        'callback' => [static::class, 'ajaxElementEntitiesSubform'],
        'wrapper' => $ajax_wrapper_id_entities,
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
      '#attributes' => [
        'class' => ['person-type'],
      ],
    ];

    if ($change_it && isset($for_bundle)) {
      list($person_entity_type_id, $person_bundle) = explode(':', $for_bundle);

      $element['entities']['person'] = [
        '#type' => 'container',
        '#tree' => TRUE,
        '#attributes' => [
          'class' => ['person-container'],
        ],
      ];

      $person_subform = &$element['entities']['person'];

      // Registrant
      $person_subform['registrant'] = [
        '#tree' => TRUE,
        '#open' => TRUE,
        '#title' => t('Registrant metadata'),
        '#parents' => array_merge($parents, ['entities', 'person', 'registrant']),
      ];

      $registrant = Registrant::create();
      if ($arity_is_single) {
        $first_registrant = reset($people);
        if ($first_registrant) {
          $registrant = $first_registrant;
        }
      }

      $display = entity_get_form_display('registrant', 'registrant', 'default');
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
          '#validate' => [],
          '#submit' => [
            [static::class, 'submitMyself'],
          ],
        ];
      }
      else {
        $entity_type = $entity_type_manager->getDefinition($person_entity_type_id);

        // Existing person
        $person_subform['existing'] = [
          '#type' => 'details',
          '#open' => TRUE,
          '#title' => t('Existing @entity_type', ['@entity_type' => $entity_type->getLabel()]),
          '#identity_element_existing_container' => TRUE,
          '#attributes' => [
            'class' => ['existing-container'],
          ],
          '#access' => static::countReferenceableEntities($event, $person_entity_type_id) > 0,
        ];
        $person_subform['existing']['existing_autocomplete'] = [
          '#type' => 'entity_autocomplete',
          '#title' => t('Existing @entity_type', ['@entity_type' => $entity_type->getLabel()]),
          '#target_type' => $person_entity_type_id,
          // @todo bundle!??!
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
          '#validate' => [],
          '#submit' => [
            [static::class, 'submitExisting'],
          ],
        ];

        if ($arity_is_multiple) {
          $person_subform['existing']['actions']['add_existing']['#validate'][] = [static::class, 'validateExisting'];
        }

        // New entity
        $create = FALSE;
        if (isset($element['#allow_creation'][$person_entity_type_id])) {
          $create = $entity_type_manager->getAccessControlHandler($person_entity_type_id)
            ->createAccess();
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
          $new_person = $entity_storage->create();

          $display = entity_get_form_display($person_entity_type_id, $person_bundle, 'default');
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
              [static::class, 'validateCreateAjax'],
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
            '#submit' => [
              [static::class, 'submitToggleCreateEntity'],
            ],
          ];
        }
        else {
          $person_subform['new_person']['load_create_form'] = [
            '#type' => 'submit',
            '#value' => t('Create new @entity_type', ['@entity_type' => $entity_type->getLabel()]),
            '#ajax' => [
              'callback' => [static::class, 'ajaxElementEntitiesSubform'],
              'wrapper' => $ajax_wrapper_id_entities,
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
   * Validator for the element.
   */
  public static function validateIdentityElement(&$element, FormStateInterface $form_state, &$complete_form) {
    $trigger_is_child = static::findElement($complete_form, $form_state);

    // If trigger is not a child of the element, then clean up the element value
    // so it only contains people entities.
    if (!isset($trigger_is_child)) {
      $registrants = $element['#value'];

      $arity_is_single = static::getArity($element, $form_state) === 'single';
      if ($arity_is_single) {
        $registrants = array_slice($registrants, 0, 1);
        $change_it = static::getChangeIt($element, $form_state);
        if ($change_it) {
          // Ensure if the change it is TRUE and single form is open then throw
          // error.
          $form_state->setError($element, t('You must select a person.'));
        }
      }

      $form_state->setValueForElement($element, $registrants);
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
    return static::findElement($form, $form_state);
  }

  /**
   * Ajax callback to return the entities sub-form.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The entities sub-form.
   */
  public static function ajaxElementEntitiesSubform(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    $element = static::findElement($form, $form_state);
    return $element['entities'];
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
    $element = static::findElement($form, $form_state);
    $autocomplete_tree = array_merge($element['#parents'], ['entities', 'person', 'existing', 'existing_autocomplete']);

    $element_existing = NestedArray::getValue($element, ['entities', 'person', 'existing', 'existing_autocomplete']);
    $existing_entity_type = $element_existing['#target_type'];
    $existing_value = NestedArray::getValue($form_state->getValues(), $autocomplete_tree);

    $identity = \Drupal::entityTypeManager()->getStorage($existing_entity_type)
      ->load($existing_value);

    if (static::identityExists($element, $form_state, $identity)) {
      $form_state->setError(NestedArray::getValue($form, $autocomplete_tree), t('Person is already on this registration.'));
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
  public static function validateCreateAjax(array &$form, FormStateInterface $form_state) {
    $element = static::findElement($form, $form_state);

    $new_person_tree = array_merge($element['#parents'], ['entities', 'person', 'new_person', 'newentityform']);
    $subform_newentity = NestedArray::getValue($form, $new_person_tree);

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

    $element = static::findElement($form, $form_state);

    static::setChangeIt($form, $form_state, TRUE);

    $new_arity = static::getArity($element, $form_state);
    if ($new_arity === 'single') {
      static::clearPeopleFormInput($element, $form_state);
      static::setForBundleAsFirstRegistrant($element, $form_state);
    }
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
    $element = static::findElement($form, $form_state);

    /** @var \Drupal\rng\RegistrantInterface[] $people */
    $people = $element['#value'];

    $new_arity = $trigger['#value'];
    static::setArity($element, $form_state, $new_arity);

    if ((count($people) > 0)) {
      if ($new_arity === 'single') {
        static::clearPeopleFormInput($element, $form_state);
        static::setForBundleAsFirstRegistrant($element, $form_state);
      }
      else {
        static::clearPeopleFormInput($element, $form_state);
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

    $element = static::findElement($form, $form_state);

    // Registrant.
    $registrant_tree = array_merge($element['#parents'], ['entities', 'person', 'registrant']);
    $subform_registrant = NestedArray::getValue($form, $registrant_tree);

    /** @var \Drupal\rng\RegistrantInterface $registrant */
    $registrant = $form_state->get('registrant__entity');
    $display = $form_state->get('registrant__form_display');
    $display->extractFormValues($registrant, $subform_registrant, $form_state);

    static::clearPeopleFormInput($element, $form_state);

    $current_user = \Drupal::currentUser();
    if ($current_user->isAuthenticated()) {
      $person = User::load($current_user->id());
      $registrant->setIdentity($person);
    }

    $arity = static::getArity($element, $form_state);
    if ($arity === 'single') {
      static::replaceFirstRegistrant($form, $form_state, $registrant);
      static::setChangeIt($form, $form_state, FALSE);
    }
    else {
      static::addRegistrant($form, $form_state, $registrant);
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

    $element = static::findElement($form, $form_state);

    $autocomplete_tree = array_merge($element['#parents'], ['entities', 'person', 'existing', 'existing_autocomplete']);
    $existing_value = NestedArray::getValue($form_state->getValues(), $autocomplete_tree);
    if (!isset($existing_value)) {
      // No input.
      return;
    }

    // Registrant.
    $registrant_tree = array_merge($element['#parents'], ['entities', 'person', 'registrant']);
    $subform_registrant = NestedArray::getValue($form, $registrant_tree);

    /** @var \Drupal\rng\RegistrantInterface $registrant */
    $registrant = $form_state->get('registrant__entity');
    $display = $form_state->get('registrant__form_display');
    $display->extractFormValues($registrant, $subform_registrant, $form_state);

    static::clearPeopleFormInput($element, $form_state);

    $subform_autocomplete = NestedArray::getValue($form, $autocomplete_tree);
    $existing_entity_type = $subform_autocomplete['#target_type'];
    $person = \Drupal::entityTypeManager()->getStorage($existing_entity_type)
      ->load($existing_value);
    $registrant->setIdentity($person);

    $arity = static::getArity($element, $form_state);
    if ($arity === 'single') {
      static::replaceFirstRegistrant($form, $form_state, $registrant);
      static::setChangeIt($form, $form_state, FALSE);
    }
    else {
      static::addRegistrant($form, $form_state, $registrant);
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

    $element = static::findElement($form, $form_state);

    // New entity
    $new_entity_tree = array_merge($element['#parents'], ['entities', 'person', 'new_person', 'newentityform']);
    $subform_new_entity = NestedArray::getValue($form, $new_entity_tree);

    // Save the entity.
    /** @var \Drupal\Core\Entity\EntityInterface $new_person */
    $new_person = $form_state->get('newentity__entity');
    $display = $form_state->get('newentity__form_display');
    $display->extractFormValues($new_person, $subform_new_entity, $form_state);
    $new_person->save();

    // Registrant.
    $registrant_tree = array_merge($element['#parents'], ['entities', 'person', 'registrant']);
    $subform_registrant = NestedArray::getValue($form, $registrant_tree);

    /** @var \Drupal\rng\RegistrantInterface $registrant */
    $registrant = $form_state->get('registrant__entity');
    $display = $form_state->get('registrant__form_display');
    $display->extractFormValues($registrant, $subform_registrant, $form_state);

    static::clearPeopleFormInput($element, $form_state);

    $registrant->setIdentity($new_person);

    $arity = static::getArity($element, $form_state);
    if ($arity === 'single') {
      static::replaceFirstRegistrant($form, $form_state, $registrant);
      static::setChangeIt($form, $form_state, FALSE);
      static::setShowCreateEntitySubform($form, $form_state, FALSE);
    }
    else {
      static::addRegistrant($form, $form_state, $registrant);
      static::setShowCreateEntitySubform($form, $form_state, FALSE);
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
    static::setShowCreateEntitySubform($form, $form_state, $trigger['#toggle_create_entity']);
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

    $element = static::findElement($form, $form_state);
    $trigger = $form_state->getTriggeringElement();
    $row = $trigger['#identity_element_registrant_row'];

    $registrants = static::getRegistrants($element, $form_state);
    unset($registrants[$row]);
    static::setRegistrants($element, $form_state, $registrants);
  }

  /**
   * Traverses the triggering element tree until this element is found.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array|NULL
   *   The element, or NULL if the element is not found.
   */
  protected static function findElement(array $form, FormStateInterface $form_state) {
    return static::findElementWithProperties($form, $form_state, ['#identity_element_root' => TRUE]);
  }

  /**
   * Traverses the triggering element tree until an element is found.
   *
   * Traverses parents tree from the triggering element to find the element
   * with the passed in properties.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $element_properties
   *   Traverse up until finding an element with the properties with these keys
   *   and values.
   *
   * @return array|NULL
   *   The requested element, or NULL if the element is not found.
   */
  protected static function findElementWithProperties(array $form, FormStateInterface $form_state, array $element_properties) {
    $trigger = $form_state->getTriggeringElement();
    $parents = $trigger['#parents'];

    // In case $form is the element itself.
    $element = $form;

    while (!isset($element) || array_diff_assoc($element_properties, $element)) {
      $element = NestedArray::getValue($form, $parents);
      if (array_pop($parents) === NULL) {
        return NULL;
      }
    }
    return $element;
  }

  public static function addRegistrant(array $form, FormStateInterface $form_state, RegistrantInterface $registrant) {
    $element = static::findElement($form, $form_state);
    $registrants = static::getRegistrants($element, $form_state);
    $registrants[] = $registrant;
    static::setRegistrants($element, $form_state, $registrants);
  }

  public static function replaceFirstRegistrant(array $form, FormStateInterface $form_state, RegistrantInterface $registrant) {
    $element = static::findElement($form, $form_state);

    $registrants = static::getRegistrants($element, $form_state);

    // Get first key, or use 0 if there is none.
    $key = key($registrants);
    $key = ($key !== NULL) ? $key : 0;
    $registrants[$key] = $registrant;

    static::setRegistrants($element, $form_state, $registrants);
  }

  /**
   * Gets registrant from form state.
   *
   * @param array $element
   *   An associative array containing the form structure of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\rng\RegistrantInterface[]
   *   An array of registrants.
   */
  public static function getRegistrants(array $element, FormStateInterface $form_state) {
    return $form_state->get(array_merge($element['#parents'], ['registrants'])) ?: $element['#value'];
  }

  /**
   * Sets registrants in form state.
   *
   * @param array $element
   *   An associative array containing the form structure of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\rng\RegistrantInterface[]
   *   An array of registrants.
   */
  public static function setRegistrants(array $element, FormStateInterface $form_state, array $registrants) {
    $form_state->set(array_merge($element['#parents'], ['registrants']), $registrants);
  }

  /**
   * Get form state for change_it.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return boolean
   *   The current value for change_it.
   */
  public static function getChangeIt(array $form, FormStateInterface $form_state) {
    $element = static::findElement($form, $form_state);
    return (bool) $form_state->get(array_merge($element['#parents'], ['change_it']));
  }

  /**
   * Set form state for change_it.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param $value
   */
  public static function setChangeIt(array $form, FormStateInterface $form_state, $value) {
    $element = static::findElement($form, $form_state);
    $form_state->set(array_merge($element['#parents'], ['change_it']), $value);
  }

  /**
   * Get form state for for_arity.
   */
  public static function getArity(array $element, FormStateInterface $form_state) {
    $arity = $form_state->get(array_merge($element['#parents'], ['for_arity']));
    if ($arity === NULL) {
      $values = NestedArray::getValue($form_state->getUserInput(), $element['#parents']);
      if (isset($values['for_arity'])) {
        $arity = $values['for_arity'];
      }
      else {
        $registrants = $element['#value'];
        $arity = (count($registrants) > 1) ? 'multiple' : 'single';
      }
    }

    return $arity;
  }

  /**
   * Set form state for for_arity.
   *
   * Arity needs to persist in case the user has multiple registrants, then
   * selects 'Single', then selects 'Change' again.
   *
   */
  public static function setArity(array $element, FormStateInterface $form_state, $arity) {
    $form_state->set(array_merge($element['#parents'], ['for_arity']), $arity);
  }

  /**
   * Get form state for opening the create-an-entity sub-form.
   *
   * @param array $element
   *   An associative array containing the form structure of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return boolean
   *   Wther the create-an-entity sub-form is open.
   */
  public static function getShowCreateEntitySubform(array $element, FormStateInterface $form_state) {
    return (bool) $form_state->get(array_merge($element['#parents'], ['show_entity_create_form']));
  }

  /**
   * Set form state for opening the create-an-entity sub-form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param $value
   */
  public static function setShowCreateEntitySubform(array $form, FormStateInterface $form_state, $value) {
    $element = static::findElement($form, $form_state);
    $form_state->set(array_merge($element['#parents'], ['show_entity_create_form']), $value);
  }

  /**
   * Clear user input from people sub-forms.
   *
   * @param array $element
   *   An associative array containing the form structure of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function clearPeopleFormInput($element, FormStateInterface $form_state) {
    $autocomplete_tree = array_merge($element['#parents'], ['entities', 'person', 'existing','existing_autocomplete']);
    NestedArray::unsetValue($form_state->getUserInput(), $autocomplete_tree);

    $registrant_tree = array_merge($element['#parents'], ['entities', 'person', 'registrant']);
    NestedArray::unsetValue($form_state->getUserInput(), $registrant_tree);

    $new_entity_tree = array_merge($element['#parents'], ['entities', 'person', 'new_person', 'newentityform']);
    NestedArray::unsetValue($form_state->getUserInput(), $new_entity_tree);
  }

  /**
   * Load first registrant into form inputs.
   *
   * @param array $element
   *   An associative array containing the form structure of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function setForBundleAsFirstRegistrant($element, FormStateInterface $form_state) {
    /** @var \Drupal\rng\RegistrantInterface[] $registrants */
    $registrants = $element['#value'];

    $registrant = reset($registrants);
    if ($registrant) {
      $identity = $registrant->getIdentity();
      $entity_type = $identity->getEntityTypeId();
      $new_value = "$entity_type:$entity_type";

      $for_bundle_tree = array_merge($element['#parents'], ['entities', 'for_bundle']);
      NestedArray::setValue($form_state->getUserInput(), $for_bundle_tree, $new_value);
    }
  }

  /**
   * Determine if an identity is already used.
   *
   * @param array $element
   *   An associative array containing the form structure of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Core\Entity\EntityInterface $identity
   *   An identity to check.
   *
   * @return boolean
   *   Whether the identity is already used.
   */
  public static function identityExists($element, $form_state, EntityInterface $identity) {
    $registrants = static::getRegistrants($element, $form_state);
    foreach ($registrants as $registrant) {
      if (($registrant->getIdentity()->id() == $identity->id()) && ($registrant->getIdentity()->getEntityTypeId() == $identity->getEntityTypeId())) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Count referenceable identities for an event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   The event entity.
   * @param $entity_type_id
   *   The identity entity type ID.
   * @return int
   *   The count of referencable entities.
   */
  public static function countReferenceableEntities(EntityInterface $event, $entity_type_id) {
    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager */
    $selection_manager = \Drupal::service('plugin.manager.entity_reference_selection');

    $options = [
      'target_type' => $entity_type_id,
      'handler' => 'rng_register',
      'handler_settings' => [
        'event_entity_type' => $event->getEntityTypeId(),
        'event_entity_id' => $event->id(),
      ],
    ];

    /* @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $selection */
    $selection = $selection_manager->getInstance($options);
    return $selection->countReferenceableEntities();
  }

}
