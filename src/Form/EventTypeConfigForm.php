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
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Form controller for event config entities.
 */
class EventTypeConfigForm extends EntityForm {
  /**
   * Constructs a EventTypeConfigForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
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
            if (!rng_entity_bundle($entity_type->id(), $bundle)) {
              $bundle_options[$entity_type->getLabel()][$entity_type->id() . '.' . $bundle] = $bundle_info['label'];
            }
          }
        }
      }

      $form['bundle'] = array(
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

    // Mirror permission
    $form['access']['mirror_update'] = array(
      '#group' => 'settings',
      '#type' => 'checkbox',
      '#title' => t('Mirror manage registrations with update permission'),
      '#description' => t('Allow users to <strong>manage registrations</strong> if they have <strong>update</strong> permission on an event entity.'),
      '#default_value' => $event_type_config->mirror_update_permission,
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $this->entity = $this->buildEntity($form, $form_state);
  }

  public function save(array $form, FormStateInterface $form_state) {
    $event_type_config = $this->getEntity();

    if ($event_type_config->isNew()) {
      $bundle = $form_state->getValue('bundle');
      list($event_type_config->entity_type, $event_type_config->bundle) = explode('.', $bundle);
    }

    $event_type_config->mirror_update_permission = $form_state->getValue('mirror_update');

    $status = $event_type_config->save();

    $message = ($status == SAVED_UPDATED) ? '%label event type was updated.' : '%label event type was added.';
    $url = $event_type_config->urlInfo();
    $t_args = ['%label' => $event_type_config->label(), 'link' => $this->l(t('Edit'), $url)];

    drupal_set_message($this->t($message, $t_args));
    $this->logger('rng')->notice($message, $t_args);

    $form_state->setRedirect('rng.event_type_config.overview');
  }
}