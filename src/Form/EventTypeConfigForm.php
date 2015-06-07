<?php

/**
 * @file
 * Contains \Drupal\rng\Form\EventTypeConfigForm.
 */

namespace Drupal\rng\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\rng\EventManagerInterface;

/**
 * Form controller for event config entities.
 */
class EventTypeConfigForm extends EntityForm {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a EventTypeConfigForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, EventManagerInterface $event_manager) {
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('rng.event_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $event_type_config = $this->entity;

    if (!$event_type_config->isNew()) {
      $form['#title'] = $this->t('Edit event type %label configuration', array(
        '%label' => $event_type_config->label(),
      ));
    }

    if ($event_type_config->isNew()) {
      $bundle_options = array();
      // Generate a list of fieldable bundles which are not events.
      foreach ($this->entityManager->getDefinitions() as $entity_type) {
        if ($entity_type->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface')) {
          foreach ($this->entityManager->getBundleInfo($entity_type->id()) as $bundle => $bundle_info) {
            if (!$this->eventManager->eventType($entity_type->id(), $bundle)) {
              $bundle_options[$entity_type->getLabel()][$entity_type->id() . '.' . $bundle] = $bundle_info['label'];
            }
          }
        }
      }

      if ($this->moduleHandler->moduleExists('node')) {
        $form['#attached']['library'][] = 'rng/rng.admin';
        $form['entity_type'] = [
          '#type' => 'radios',
          '#options' => NULL,
          '#title' => $this->t('Event entity type'),
          '#required' => TRUE,
        ];
        $form['entity_type']['node']['radio'] = [
          '#type' => 'radio',
          '#title' => $this->t('Create a new content type'),
          '#description' => $this->t('Create a content type to use as an event type.'),
          '#return_value' => "node",
          '#parents' => array('entity_type'),
          '#default_value' => 'node',
        ];

        $form['entity_type']['existing']['radio'] = [
          '#type' => 'radio',
          '#title' => $this->t('Use existing bundle'),
          '#description' => $this->t('Use an existing entity/bundle combination.'),
          '#return_value' => "existing",
          '#parents' => array('entity_type'),
          '#default_value' => '',
        ];

        $form['entity_type']['existing']['container'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['rng-radio-indent'],
          ],
        ];
      }

      $form['entity_type']['existing']['container']['bundle'] = array(
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#options' => $bundle_options,
        '#default_value' => $event_type_config->id(),
        '#required' => TRUE,
        '#disabled' => !$event_type_config->isNew(),
        '#empty_option' => $bundle_options ?: t('No Bundles Available'),
      );
    }

    $form['settings'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
      '#open' => TRUE,
    );

    // Mirror permission.
    $form['access']['mirror_update'] = array(
      '#group' => 'settings',
      '#type' => 'checkbox',
      '#title' => t('Mirror manage registrations with update permission'),
      '#description' => t('Allow users to <strong>manage registrations</strong> if they have <strong>update</strong> permission on an event entity.'),
      '#default_value' => ((boolean) (isset($event_type_config->mirror_update_permission) ? $event_type_config->mirror_update_permission : TRUE)),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $this->entity = $this->buildEntity($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $event_type_config = $this->getEntity();

    if ($event_type_config->isNew()) {
      if ($this->moduleHandler->moduleExists('node') && ($form_state->getValue('entity_type') == 'node')) {
        $node_type = $this->createContentType('event');
        $t_args = [
          '%name' => $node_type->label(),
          '!link' => $node_type->link(),
        ];
        drupal_set_message(t('The content type !link has been added.', $t_args));
        $event_type_config->entity_type = $node_type->getEntityType()->getBundleOf();
        $event_type_config->bundle = $node_type->id();
      }
      else {
        $bundle = $form_state->getValue('bundle');
        list($event_type_config->entity_type, $event_type_config->bundle) = explode('.', $bundle);
      }
    }

    // Set to the access operation for event.
    $event_type_config->mirror_update_permission = $form_state->getValue('mirror_update') ? 'update' : '';

    $status = $event_type_config->save();

    $message = ($status == SAVED_UPDATED) ? '%label event type updated.' : '%label event type added.';
    $url = $event_type_config->urlInfo();
    $t_args = ['%label' => $event_type_config->id(), 'link' => $this->l(t('Edit'), $url)];

    drupal_set_message($this->t($message, $t_args));
    $this->logger('rng')->notice($message, $t_args);

    $form_state->setRedirect('rng.event_type_config.overview');
  }

  /**
   * Creates a content type.
   *
   * Attempts to create a content type with ID $prefix, $prefix_1, $prefix_2...
   *
   * @param string $prefix
   *   A string prefix for the node type ID.
   *
   * @return \Drupal\node\NodeTypeInterface
   *   A node type entity.
   */
  private function createContentType($prefix) {
    // Generate a unique id
    $i = 0;
    $separator = '_';
    $id = $prefix;
    while ($this->entityManager->getStorage('node_type')->load($id)) {
      $i++;
      $id = $prefix . $separator . $i;
    }

    $node_type = $this->entityManager->getStorage('node_type')->create([
      'type' => $id,
      'name' => $id,
    ]);
    $node_type->save();
    return $node_type;
  }

}
