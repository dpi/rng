<?php

/**
 * @file
 * Contains \Drupal\rng\Form\RegistrationForm.
 */

namespace Drupal\rng\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager;
use Drupal\rng\EventManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form controller for registrations.
 */
class RegistrationForm extends ContentEntityForm {

  /**
   * @var \Drupal\rng\RegistrationInterface
   */
  protected $entity;

  /**
   * The selection plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager
   */
  protected $selectionManager;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a registration form.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager $selection_manager
   *   The selection plugin manager.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, SelectionPluginManager $selection_manager, EventManagerInterface $event_manager) {
    parent::__construct($entity_manager);
    $this->selectionManager = $selection_manager;
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.entity_reference_selection'),
      $container->get('rng.event_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $current_user = $this->currentUser();
    $registration = $this->getEntity();
    $event = $registration->getEvent();

    if (!$registration->isNew()) {
      $form['#title'] = $this->t('Edit Registration',
        array(
          '%event_label' => $event->label(),
          '%event_id' => $event->id(),
          '%registration_id' => $registration->id(),
        )
      );
    }

    $form = parent::form($form, $form_state, $registration);

    if ($registration->isNew()) {
      $form['identity_information'] = [
        '#type' => 'details',
        '#title' => $this->t('Identity'),
        '#description' => $this->t('Select an identity to associate with this registration.'),
        '#open' => TRUE,
      ];
      $form['identity_information']['identity'] = [
        '#type' => 'radios',
        '#options' => NULL,
        '#title' => $this->t('Identity'),
        '#required' => TRUE,
      ];

      $self = FALSE;
      $event_meta = $this->eventManager->getMeta($event);
      // Create a register radio option for current user.
      // list of entity reference field types, ordered by radio default priority.
      $entity_types = $event_meta->getIdentityTypes();

      // Radio order is alphabetical. (ex: self).
      $sorted = $entity_types;
      ksort($sorted);
      foreach ($sorted as $entity_type_id) {
        $options = [
          'target_type' => $entity_type_id,
          'handler' => 'rng_register',
          'handler_settings' => ['event_entity_type' => $event->getEntityTypeId(), 'event_entity_id' => $event->id()],
        ];

        /* @var $selection \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface */
        $selection = $this->selectionManager->getInstance($options);
        $count = $selection->countReferenceableEntities();

        if ($entity_type_id == 'user') {
          // If duplicate registrants is allowed || user is not already a registrant.
          if ($event_meta->identitiesCanRegister('user', [$current_user->id()])) {
            $self = TRUE;
            $count--;
          }
        }

        if ($count > 0) {
          $entity_type = $this->entityManager->getDefinition($entity_type_id);

          $form['identity_information']['identity'][$entity_type_id] = [
            '#prefix' => '<div class="form-item container-inline">',
            '#suffix' => '</div>'
          ];
          $form['identity_information']['identity'][$entity_type_id]['radio'] = [
            '#type' => 'radio',
            '#title' => $entity_type->getLabel(),
            '#return_value' => "$entity_type_id:*",
            '#parents' => array('identity'),
            '#default_value' => '',
          ];
          $form['identity_information']['identity'][$entity_type_id]['autocomplete'] = [
            '#type' => 'entity_autocomplete',
            '#title' => $entity_type->getLabel(),
            '#title_display' => 'invisible',
            '#target_type' => $entity_type_id,
            '#selection_handler' => 'rng_register',
            '#selection_settings' => [
              'event_entity_type' => $event->getEntityTypeId(),
              'event_entity_id' => $event->id(),
            ],
            '#tags' => FALSE,
            '#parents' => array('entity', $entity_type_id),
          ];

          // Add link: The 'add' link is something invented by RNG, it is not a
          // standard link.
          // @todo: replace with add and reference ajax popup + form.
          if ($add_link = $entity_type->getLinkTemplate('add-form')) {
            $form['identity_information']['identity'][$entity_type_id]['add'] = [
              '#type' => 'link',
              '#title' => $this->t('Add @entity_type', ['@entity_type' => $entity_type->getLabel()]),
              '#url' => Url::fromUserInput($entity_type->getLinkTemplate('add-form')),
              '#prefix' => '[',
              '#suffix' => ']',
            ];
          }
        }
      }

      if ($self) {
        $form['identity_information']['identity']['self'] = [
          '#type' => 'radio',
          '#title' => t('My account: %username', array('%username' => $current_user->getUsername())),
          '#return_value' => 'user:' . $current_user->id(),
          '#parents' => array('identity'),
          '#default_value' => TRUE,
          '#weight' => -100,
        ];
      }
      else {
        // Self will always be default, if it exists.
        // Otherwise apply default based on $entity_types array order.
        foreach ($entity_types as $entity_type_id) {
          // Not all $entity_types are created, depends if there are any
          // referenceable entities.
          if (isset($form['identity_information']['identity'][$entity_type_id])) {
            $form['identity_information']['identity'][$entity_type_id]['radio']['#default_value'] = TRUE;
            break;
          }
        }
      }

      $form['identity_information']['redirect_identities'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Add additional identities after saving.'),
        '#default_value' => FALSE,
        // @todo: Hide option until user has ability to add more identities.
        '#access' => FALSE,
      ];
    }
    else {
      $form['revision_information'] = array(
        '#type' => 'details',
        '#title' => $this->t('Revisions'),
        '#optional' => TRUE,
        '#open' => $current_user->hasPermission('administer rng'),
      );
      $form['revision'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#description' => $this->t('Revisions record changes between saves.'),
        '#default_value' => FALSE,
        '#access' => $current_user->hasPermission('administer rng'),
        '#group' => 'revision_information',
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $registration = $this->entity;

    $t_args = [
      '@type' => $registration->bundle(),
      '%label' => $registration->label(),
      '%id' => $registration->id(),
    ];

    if ($registration->isNew()) {
      // Add registrant.
      list($entity_type, $entity_id) = explode(':', $form_state->getValue('identity'));
      if ($entity_id == '*') {
        $references = $form_state->getValue('entity');
        if (is_numeric($references[$entity_type])) {
          $entity_id = $references[$entity_type];
        }
      }

      if ($identity = entity_load($entity_type, $entity_id)) {
        $registration->addIdentity($identity);
      }
    }
    else {
      $registration->setNewRevision(!$form_state->isValueEmpty('revision'));
    }

    if ($registration->save() == SAVED_NEW) {
      drupal_set_message(t('Registration has been created.', $t_args));
    }
    else {
      drupal_set_message(t('Registration was updated.', $t_args));
    }

    if ($registration->id()) {
      if ($registration->access('view')) {
        $route_name = $form_state->getValue('redirect_identities') ? 'entity.registration.registrants' : 'entity.registration.canonical';
        $form_state->setRedirect($route_name, array('registration' => $registration->id()));
      }
      else {
        $form_state->setRedirect('<front>');
      }
    }
  }

}
