<?php

namespace Drupal\rng\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Evaluates if the current date is before or after the the configured date.
 *
 * @Condition(
 *   id = "rng_current_time",
 *   label = @Translation("Current time")
 * )
 */
class CurrentTime extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   *
   * @return array
   *   - date: int: Unix timestamp of a date the rule can be triggered, as a
   *     minimum. This value is canonical, the rng_rule_schedule date is a
   *     mirror and is reflected when this condition is saved.
   *   - negate: boolean: If TRUE, this condition will evaluate if current time
   *     is after $date. If FALSE, before $date.
   */
  public function defaultConfiguration() {
    return [
      'date' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    if (is_numeric($this->configuration['date'])) {
      $date = DrupalDateTime::createFromTimestamp($this->getDate());
    }
    else {
      $date = new DrupalDateTime();
    }

    // Add administrative comment publishing options.
    $form['date'] = array(
      '#type' => 'datetime',
      '#date_date_element' => 'date',
      '#title' => $this->t('Date'),
      '#default_value' => $date,
      '#size' => 20,
      '#weight' => 50,
    );

    $form['negate'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Timing'),
      '#description' => $this->t('Condition will be true if the time when evaluating this condition is before or after the date.'),
      '#options' => [
        0 => $this->t('After this date'),
        1 => $this->t('Before this date'),
      ],
      '#default_value' => (int) $this->isNegated(),
      '#weight' => 100,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if ($date = $form_state->getValue('date')) {
      $this->configuration['date'] = $date->format('U');
    }
    else {
      $this->configuration['date'] = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $t_args = ['@date' => DrupalDateTime::createFromTimestamp($this->getDate())];
    if (!$this->isNegated()) {
      return $this->t('Current date is after @date', $t_args);
    }
    else {
      return $this->t('Current date is before @date', $t_args);
    }
  }

  /**
   * Gets the date in configuration.
   */
  function getDate() {
    return $this->configuration['date'];
  }

  /**
   * Formats the date for display.
   */
  function getDateFormatted() {
    return is_numeric($this->getDate()) ? DrupalDateTime::createFromTimestamp($this->getDate()) : $this->t('Not configured');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $date = $this->getDate();
    if ($date && is_numeric($date)) {
      if (!$this->isNegated()) {
        return time() > $date;
      }
      else {
        return time() < $date;
      }
    }
    return FALSE;
  }

}
