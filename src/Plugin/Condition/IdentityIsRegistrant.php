<?php

namespace Drupal\rng\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\rng\RNGConditionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a identity is registrant condition.
 *
 * Detects whether the identity is a registrant on the registration.
 *
 * @Condition(
 *   id = "rng_registration_identity",
 *   label = @Translation("Registration has identity"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("Registrant"),
 *       required = TRUE
 *     ),
 *     "registration" = @ContextDefinition("entity:registration",
 *       label = @Translation("Registration"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class IdentityIsRegistrant extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['description']['#markup'] = $this->t('There are no configuration options.');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Logged-in user is a registrant.');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    /* @var \Drupal\user\UserInterface $user */
    $user = $this->getContextValue('user');
    /* @var \Drupal\rng\RegistrationInterface $registration */
    $registration = $this->getContextValue('registration');

    // Does not support new registrations ('create' operation).
    if (!$registration->isNew()) {
      return $registration->hasIdentity($user);
    }

    return FALSE;
  }

}
