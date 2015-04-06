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
 * Builds a rule with pre-made message action.
 */
class MessageActionForm extends FormBase {

  /**
   * @var \Drupal\Core\Action\ConfigurableActionBase $actionPlugin
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
    $this->actionPlugin = $action_manager->createInstance('rng_registrant_email');
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
    return 'rng_event_message_send';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RouteMatchInterface $route_match = NULL, $event = NULL) {
    $event = clone $route_match->getParameter($event);
    $form_state->set('event', $event);

    $triggers = array(
      'now' => $this->t('Immediately, to all registrants'),
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

    $form += $this->actionPlugin->buildConfigurationForm($form, $form_state);

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Send'),
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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->actionPlugin->submitConfigurationForm($form, $form_state);

    /* @var $action \Drupal\rng\ActionInterface */
    $action = $this->entityManager->getStorage('rng_action')->create();
    $action->setPluginId($this->actionPlugin->getPluginId());
    $action->setConfiguration($this->actionPlugin->getConfiguration());
    $action->setType('action');

    $event = $form_state->get('event');
    $trigger = $form_state->getValue('trigger');
    if ($trigger == 'now') {
      $registrations = $this->eventManager->getMeta($event)->getRegistrations();
      foreach ($registrations as $registration) {
        $context = array(
          'event' => $event,
          'registration' => $registration,
        );
        $action->execute($context);
        drupal_set_message(t('Message sent to all registrants.'));
      }
    }
    else {
      $rule = $this->entityManager->getStorage('rng_rule')->create(array(
        'event' => array('entity' => $event),
        'trigger_id' => $trigger,
      ));
      $rule->save();
      $action->setRule($rule)->save();
      drupal_set_message(t('Message saved.'));
    }

    $form_state->setRedirect(
      'rng.event.' . $event->getEntityTypeId() . '.messages',
      array($event->getEntityTypeId() => $event->id())
    );
  }

}
