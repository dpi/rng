<?php

namespace Drupal\rng\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for registration types.
 */
class RegistrationTypeForm extends EntityForm {
  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(QueryFactory $query_factory) {
    $this->entityQueryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.query'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $registration_type = $this->entity;

    if (!$registration_type->isNew()) {
      $form['#title'] = $this->t('Edit registration type %label', array(
        '%label' => $registration_type->label(),
      ));
    }

    // Build the form.
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $registration_type->label(),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $registration_type->id(),
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores.',
      ),
      '#disabled' => !$registration_type->isNew(),
    );

    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#default_value' => $registration_type->description,
      '#description' => t('Description will be displayed when a user is choosing which registration type to use for an event.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Callback for `id` form element in RegistrationTypeForm->buildForm.
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
    $query = $this->entityQueryFactory->get('registration_type');
    return (bool) $query->condition('id', $entity_id)->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $registration_type = $this->getEntity();
    $status = $registration_type->save();

    $message = ($status == SAVED_UPDATED) ? '%label registration type was updated.' : '%label registration type was added.';
    $url = $registration_type->urlInfo();
    $t_args = ['%label' => $registration_type->label(), 'link' => $this->l(t('Edit'), $url)];

    drupal_set_message($this->t($message, $t_args));
    $this->logger('rng')->notice($message, $t_args);

    $form_state->setRedirect('rng.registration_type.overview');
  }

}
