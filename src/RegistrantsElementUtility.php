<?php

namespace Drupal\rng;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

class RegistrantsElementUtility {

  /**
   * @var array
   */
  protected $element;

  /**
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * RegistrantsElementUtility constructor.
   *
   * @param array $element
   *   The form array of the registrants element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function __construct(array &$element, FormStateInterface &$form_state) {
    $this->element = $element;
    $this->formState = $form_state;
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
  public static function findElement(array $form, FormStateInterface $form_state) {
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

  public function addRegistrant(RegistrantInterface $registrant) {
    $registrants = $this->getRegistrants();
    $registrants[] = $registrant;
    $this->setRegistrants($registrants);
  }

  public function replaceFirstRegistrant(RegistrantInterface $registrant) {
    $registrants = $this->getRegistrants();

    // Get first key, or use 0 if there is none.
    $key = key($registrants);
    $key = ($key !== NULL) ? $key : 0;
    $registrants[$key] = $registrant;

    $this->setRegistrants($registrants);
  }

  /**
   * Gets registrant from form state.
   *
   * @return \Drupal\rng\RegistrantInterface[]
   *   An array of registrants.
   */
  public function getRegistrants() {
    return $this->formState->get(array_merge($this->element['#parents'], ['registrants'])) ?: $this->element['#value'];
  }

  /**
   * Sets registrants in form state.
   *
   * @param \Drupal\rng\RegistrantInterface[] $registrants
   *   An array of registrants.
   */
  public function setRegistrants(array $registrants) {
    $this->formState->set(array_merge($this->element['#parents'], ['registrants']), $registrants);
  }

  /**
   * Gets identities which should skip the existing validation check.
   *
   * @return array
   *   An array of identities to skip existing check.
   */
  public function getWhitelistExisting() {
    return $this->formState->get(array_merge($this->element['#parents'], ['whitelist_existing'])) ?: [];
  }

  /**
   * Whitelist an existing identity from re-validation.
   *
   * Identities created by this element should avoid the existing check of
   * EventMetaInterface::identitiesCanRegister in case the event type does not
   * permit usage of the 'existing' subform.
   *
   * @param \Drupal\Core\Entity\EntityInterface $identity
   *   An identity to whitelist from re-validation
   */
  public function addWhitelistExisting(EntityInterface $identity) {
    $whitelisted = $this->getWhitelistExisting();
    $whitelisted[$identity->getEntityTypeId()][$identity->id()] = $identity->id();
    $this->formState->set(array_merge($this->element['#parents'], ['whitelist_existing']), $whitelisted);
  }

  /**
   * Get form state for change_it.
   *
   * @return boolean
   *   The current value for change_it.
   */
  public function getChangeIt() {
    return $this->formState->get(array_merge($this->element['#parents'], ['change_it'])) ?: FALSE;
  }

  /**
   * Set form state for change_it.
   *
   * @param $value
   */
  public function setChangeIt($value) {
    $this->formState->set(array_merge($this->element['#parents'], ['change_it']), $value);
  }

  /**
   * Get form state for for_arity.
   */
  public function getArity() {
    $arity = $this->formState->get(array_merge($this->element['#parents'], ['for_arity']));
    if ($arity === NULL) {
      $values = NestedArray::getValue($this->formState->getUserInput(), $this->element['#parents']);
      if (isset($values['for_arity'])) {
        $arity = $values['for_arity'];
      }
      else {
        // Default.
        $minimum = $this->element['#registrants_minimum'];
        $maximum = $this->element['#registrants_maximum'];

        $count = count($this->element['#value']);

        if ($minimum && $minimum > 1) {
          $arity = 'multiple';
        }
        else if ($maximum && $maximum == 1) {
          $arity = 'single';
        }
        else {
          $arity = ($count > 1) ? 'multiple' : 'single';
        }
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
  public function setArity($arity) {
    $this->formState->set(array_merge($this->element['#parents'], ['for_arity']), $arity);
  }

  /**
   * Get form state for opening the create-an-entity sub-form.
   *
   * @return boolean
   *   Wther the create-an-entity sub-form is open.
   */
  public function getShowCreateEntitySubform() {
    return (bool) $this->formState->get(array_merge($this->element['#parents'], ['show_entity_create_form']));
  }

  /**
   * Set form state for opening the create-an-entity sub-form.
   *
   * @param $value
   */
  public function setShowCreateEntitySubform($value) {
    $this->formState->set(array_merge($this->element['#parents'], ['show_entity_create_form']), $value);
  }

  /**
   * Clear user input from people sub-forms.
   */
  public function clearPeopleFormInput() {
    $autocomplete_tree = array_merge($this->element['#parents'], ['entities', 'person', 'existing','existing_autocomplete']);
    NestedArray::unsetValue($this->formState->getUserInput(), $autocomplete_tree);

    $registrant_tree = array_merge($this->element['#parents'], ['entities', 'person', 'registrant']);
    NestedArray::unsetValue($this->formState->getUserInput(), $registrant_tree);

    $new_entity_tree = array_merge($this->element['#parents'], ['entities', 'person', 'new_person', 'newentityform']);
    NestedArray::unsetValue($this->formState->getUserInput(), $new_entity_tree);
  }

  /**
   * Copies form values to registrant entity properties
   *
   * @param bool $validate
   *   Optionally validate the form.
   *
   * @return \Drupal\rng\RegistrantInterface
   * A registrant entity with updated properties.
   */
  public function buildRegistrant($validate = FALSE) {
    $value = $this->formState->getTemporaryValue(array_merge(['_registrants_values'], $this->element['#parents']));
    $this->formState->setValue($this->element['#parents'], $value);

    $registrant = $this->formState->get('registrant__entity');
    $display = $this->formState->get('registrant__form_display');

    $registrant_tree = ['entities', 'person', 'registrant'];
    $subform_registrant = NestedArray::getValue($this->element, $registrant_tree);
    $display->extractFormValues($registrant, $subform_registrant, $this->formState);

    if ($validate) {
      $display->validateFormValues($registrant, $subform_registrant, $this->formState);
    }

    return $registrant;
  }

  /**
   * Load first registrant into form inputs.
   */
  public function setForBundleAsFirstRegistrant() {
    /** @var \Drupal\rng\RegistrantInterface[] $registrants */
    $registrants = $this->element['#value'];

    $registrant = reset($registrants);
    if ($registrant) {
      $identity = $registrant->getIdentity();
      $entity_type = $identity->getEntityTypeId();
      $bundle = $identity->bundle();
      $new_value = "$entity_type:$bundle";

      $for_bundle_tree = array_merge($this->element['#parents'], ['entities', 'for_bundle']);
      NestedArray::setValue($this->formState->getUserInput(), $for_bundle_tree, $new_value);
    }
  }

  /**
   * Determine if an identity is already used.
   *
   * @param \Drupal\Core\Entity\EntityInterface $identity
   *   An identity to check.
   *
   * @return boolean
   *   Whether the identity is already used.
   */
  public function identityExists(EntityInterface $identity) {
    $registrants = $this->getRegistrants();
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
   * @param string $entity_type_id
   *   The identity entity type ID.
   * @param array $bundles
   *   (optional) Identity bundles.
   *
   * @return int
   *   The count of referencable entities.
   */
  public function countReferenceableEntities(EntityInterface $event, $entity_type_id, $bundles = []) {
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

    if (!empty($bundles)) {
      $options['handler_settings']['target_bundles'] = $bundles;
    }

    /* @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $selection */
    $selection = $selection_manager->getInstance($options);
    return $selection->countReferenceableEntities();
  }

  /**
   * Generate available people type options suitable for radios element.
   *
   * @return array
   *   Options suitable for a radios element.
   */
  public function peopleTypeOptions() {
    /** @var \Drupal\rng\EventManagerInterface $event_manager */
    $event_manager = \Drupal::service('rng.event_manager');
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
    $bundle_info = \Drupal::service('entity_type.bundle.info');

    $entity_type_manager = \Drupal::entityTypeManager();
    $current_user = \Drupal::currentUser();

    $event = $this->element['#event'];
    $event_meta = $event_manager->getMeta($event);

    $for_bundles = [];

    // Create.
    foreach ($this->element['#allow_creation'] as $entity_type_id => $bundles) {
      $info = $bundle_info->getBundleInfo($entity_type_id);
      foreach ($bundles as $bundle) {
        if ($this->entityCreateAccess($entity_type_id, $bundle)) {
          $for_bundle_key = $entity_type_id . ':' . $bundle;
          $for_bundles[$for_bundle_key] = $info[$bundle]['label'];
        }
      }
    }

    // Existing.
    foreach ($this->element['#allow_reference'] as $entity_type_id => $bundles) {
      $entity_type = $entity_type_manager->getDefinition($entity_type_id);
      $info = $bundle_info->getBundleInfo($entity_type_id);
      foreach ($bundles as $bundle) {
        $for_bundle_key = $entity_type_id . ':' . $bundle;
        if (isset($for_bundles[$for_bundle_key])) {
          // Skip if there an option already exists for 'create'.
          continue;
        }

        $counting_bundle = ($entity_type->getBundleEntityType() !== NULL) ? [$bundle] : [];
        $existing_count = $this->countReferenceableEntities($event, $entity_type_id, $counting_bundle);

        // Add myself special option.
        if ($entity_type_id === 'user' && $current_user->isAuthenticated()) {
          $identity = User::load($current_user->id());
          if (!$this->identityExists($identity)) {
            if ($event_meta->identitiesCanRegister('user', [$current_user->id()])) {
              $existing_count--;
              $for_bundles['myself:'] = t('Myself');
            }
          }
        }

        if ($existing_count > 0) {
          $for_bundles[$for_bundle_key] = $info[$bundle]['label'];
        }
      }
    }

    return $for_bundles;
  }

  /**
   * Determine whether the current user can create new entities.
   *
   * @param string $entity_type_id
   *   A entity type ID.
   * @param string $bundle
   *   An entity bundle
   *
   * @return boolean
   *   Whether the current user can create new entities.
   */
  public static function entityCreateAccess($entity_type_id, $bundle) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_type = $entity_type_manager->getDefinition($entity_type_id);
    $access_control = $entity_type_manager->getAccessControlHandler($entity_type_id);

    // If entity type has bundles
    $entity_bundle = ($entity_type->getBundleEntityType() !== NULL) ? $bundle : NULL;

    return $access_control->createAccess($entity_bundle);
  }

}
