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
 *     "event" = @ContextDefinition("all",
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
    $form['description']['#markup'] = $this->t('For @operation operations.', ['@operation' => implode(' ', array_keys($this->configuration['operations']))]);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'operations' => array(),
    ) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $config = $this->configuration['operations'];
    $operations = (count($config) > 1) ? implode(', ', $config) : reset($config);

    if (!empty($this->configuration['negate'])) {
      return $this->t('The user is has @operations on event.', array('@operations' => $operations));
    }
    else {
      return $this->t('The user does not have @operations on event.', array('@operations' => $operations));
    }
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
