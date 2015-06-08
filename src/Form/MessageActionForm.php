<?php

/**
 * @file
 * Contains \Drupal\rng\Form\MessageActionForm.
 */

namespace Drupal\rng\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Action\ActionManager;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\rng\EventManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Creates a rule with a rng_courier_message action.
 */
class MessageActionForm extends FormBase {

  /**
   * @var \Drupal\rng\Plugin\Action\CourierTemplateCollection $actionPlugin
   */
  protected $actionPlugin;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new MessageActionForm object.
   *
   * @param \Drupal\Core\Action\ActionManager $action_manager
   *   The action manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(ActionManager $action_manager, EntityManagerInterface $entity_manager, EventManagerInterface $event_manager) {
    $this->actionPlugin = $action_manager->createInstance('rng_courier_message');
    $this->entityManager = $entity_manager;
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.action'),
      $container->get('entity.manager'),
      $container->get('rng.event_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rng_event_message_create';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RouteMatchInterface $route_match = NULL, $event = NULL) {
    $event = clone $route_match->getParameter($event);
    $this->actionPlugin->setConfiguration(['active' => FALSE]);
    $form_state->set('event', $event);

    $triggers = array(
      $this->t('Registrations') => array(
        'entity:registration:new' => $this->t('When registrations are created.'),
        'entity:registration:update' => $this->t('When registrations are updated.'),
      ),
    );

    $form['trigger'] = array(
      '#type' => 'select',
      '#title' => $this->t('Trigger'),
      '#description' => $this->t('When should this message be sent?'),
      '#options' => $triggers,
      '#default_value' => 'now',
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Add message and edit templates'),
    );
    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute(
        'rng.event.' . $event->getEntityTypeId() . '.messages',
        array($event->getEntityTypeId() => $event->id())
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->actionPlugin->submitConfigurationForm($form, $form_state);

    if (!$template_collection = $this->actionPlugin->getTemplateCollection()) {
      drupal_set_message(t('Unable to create templates.', 'error'));
      return;
    }

    $event = $form_state->get('event');
    $context = $this->entityManager->getStorage('courier_context')
      ->load('rng_registration_' . $event->getEntityTypeId());
    if (!$context) {
      throw new \Exception(sprintf('No context available for %s', $event->getEntityTypeId()));
    }
    $template_collection->setContext($context);
    $template_collection->setOwner($event);
    $template_collection->save();
    drupal_set_message(t('Templates created.'));

    /* @var $action \Drupal\rng\RuleComponentInterface*/
    $action = $this->entityManager->getStorage('rng_rule_component')->create();
    $action->setPluginId($this->actionPlugin->getPluginId());
    $action->setConfiguration($this->actionPlugin->getConfiguration());
    $action->setType('action');

    $trigger_id = $form_state->getValue('trigger');

    /** @var \Drupal\rng\RuleInterface $rule */
    $rule = $this->entityManager->getStorage('rng_rule')->create(array(
      'event' => array('entity' => $event),
      'trigger_id' => $trigger_id,
    ));
    $rule->save();
    $action->setRule($rule)->save();

    $form_state->setRedirectUrl($action->urlInfo('edit-form'));
  }

}
