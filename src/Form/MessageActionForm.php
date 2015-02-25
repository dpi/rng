<?php

/**
 * @file
 * Contains \Drupal\rng\Form\MessageActionForm.
 */

namespace Drupal\rng\Form;

use Drupal\Core\Action\ActionManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
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
   * Constructs a new BanAdmin object.
   *
   * @param \Drupal\Code\Action\ActionManager $action_manager
   *   The action manager.
   */
  public function __construct(ActionManager $action_manager) {
    $this->actionPlugin = $action_manager->createInstance('rng_registrant_email');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.action')
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

    return $form;
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->actionPlugin->submitConfigurationForm($form, $form_state);
    $action = entity_create('rng_action');
    $action->setActionID($this->actionPlugin->getPluginId());
    $action->setConfiguration($this->actionPlugin->getConfiguration());
    $event = $form_state->get('event');

    $trigger = $form_state->getValue('trigger');
    if ($trigger == 'now') {
      $registration_ids = \Drupal::entityQuery('registration')
        ->condition('event__target_type', $event->getEntityTypeId(), '=')
        ->condition('event__target_id', $event->id(), '=')
        ->execute();
      foreach (entity_load_multiple('registration', $registration_ids) as $registration) {
        $context = array(
          'event' => $event,
          'registration' => $registration,
        );
        $action->execute($context);
        drupal_set_message(t('Message sent to all registrants.'));
      }
    }
    else {
      $rule = entity_create('rng_rule', array(
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