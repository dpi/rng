<?php

namespace Drupal\rng\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
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
 *       required = TRUE
 *     ),
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class EventOperation extends ConditionPluginBase {

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
    $operations = [];
    foreach ($this->configuration['operations'] as $operation => $granted) {
      if ($granted) {
        $operations[] = $this->t("%operation", ['%operation' => $operation]);
      }
    }

    return $this->t(
      !$this->isNegated() ? 'Logged-in user has access to operations on event: @operations' : 'Logged-in user does not have access to operations on event: @operations',
      ['@operations' => count($operations) > 1 ? implode($this->t(' and '), $operations) : reset($operations)]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $operation = 'manage event';
    /* @var \Drupal\user\UserInterface $user */
    $user = $this->getContextValue('user');
    /* @var \Drupal\Core\Entity\EntityInterface $event */
    $event = $this->getContextValue('event');
    return $event->access($operation);
  }

}
