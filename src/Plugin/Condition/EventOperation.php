<?php

/**
 * @file
 * Contains \Drupal\rng\Plugin\Condition\EventOperation.
 */

namespace Drupal\rng\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\rng\RNGConditionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an identity has operation permission on event condition.
 *
 * Use registration context so it does not trigger on 'create' operations.
 *
 * @Condition(
 *   id = "rng_event_operation",
 *   label = @Translation("Operation on event"),
 *   context = {
 *     "event" = @ContextDefinition("entity",
 *       label = @Translation("Event"),
 *       required = FALSE
 *     ),
 *     "registration" = @ContextDefinition("entity:registration",
 *       label = @Translation("Registration"),
 *       required = TRUE
 *     ),
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User"),
 *       required = TRUE
 *     )
 *   }
 * )
 *
 */
class EventOperation extends ConditionPluginBase implements RNGConditionInterface  {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // @todo: select an operation.
    $form['description']['#markup'] = $this->t('For %operation operations.', ['%operation' => implode(' ', array_keys($this->configuration['operations']))]);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'operations' => ['manage event' => TRUE],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $operations_all = $this->configuration['operations'];
    // Filter operations where value is TRUE
    $operations = array_filter($operations_all, function ($operation) use ($operations_all) {
      return $operation;
    });

    return $this->t(
      empty($this->configuration['negate']) ? 'Logged-in user has access to @operations the event.' : 'Logged-in user does not have access @operations the event.',
      ['@operations' => count($operations) > 1 ? implode(' and ', array_keys($operations)) : key($operations)]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $operation = 'manage event';
    /* @var \Drupal\user\UserInterface $user */
    $user = $this->getContextValue('user');
    /* @var \Drupal\rng\RegistrationInterface $registration */
    $registration = $this->getContextValue('registration');
    $event = $registration->getEvent();
    return $event->access($operation);
  }

  /**
   * {@inheritdoc}
   */
  function alterQuery(&$query) {  }

}
