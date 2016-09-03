<?php

namespace Drupal\rng\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;
use Drupal\rng\Entity\RegistrationType;

/**
 * Field handler to present a link to register for an event.
 *
 * @ViewsField("rng_event_register")
 */
class LinkRegister extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['registration_type'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $registration_types = [];
    foreach (RegistrationType::loadMultiple() as $registration_type) {
      $registration_types[$registration_type->id()] = $registration_type->label();
    }

    $form['registration_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Registration type'),
      '#default_value' => $this->options['registration_type'],
      '#options' => $registration_types,
      '#empty_option' => $this->t('- Display all registration types -'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    $event = $this->getEntity($row);
    $route_parameters[$event->getEntityTypeId()] = $event->id();

    $registration_type = $this->options['registration_type'];
    if ($registration_type && RegistrationType::load($registration_type)) {
      $route = 'rng.event.' . $event->getEntityTypeId() . '.register';
      $route_parameters['registration_type'] = $registration_type;
    }
    else {
      $route = 'rng.event.' . $event->getEntityTypeId() . '.register.type_list';
    }

    return Url::fromRoute($route, $route_parameters);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Register');
  }

}
