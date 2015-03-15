<?php

/**
 * @file
 * Contains \Drupal\rng\EventMeta.
 */

namespace Drupal\rng;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityInterface;

/**
 * Meta event wrapper for RNG.
 */
class EventMeta implements EventMetaInterface {

  /**
   * The entity query factory.
   *
   * @var EntityInterface
   */
  protected $entity;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new EventMeta object.
   *
   * @param EntityManager $entity_manager
   *   The entity manager.
   * @param EntityInterface $entity
   *   The event entity.
   */
  function __construct(EntityManager $entity_manager, EntityInterface $entity) {
    $this->entityManager = $entity_manager;
    $this->entity = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityInterface $entity) {
    return new static(
      $container->get('entity.manager'),
      $entity
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEvent() {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function isAcceptingRegistrations() {
    return !empty($this->getEvent()->{EventManagerInterface::FIELD_STATUS}->value);
  }

  /**
   * {@inheritdoc}
   */
  public function duplicateRegistrantsAllowed() {
    return !empty($this->getEvent()->{EventManagerInterface::FIELD_ALLOW_DUPLICATE_REGISTRANTS}->value);
  }

  /**
   * {@inheritdoc}
   */
  public function getRegistrationTypeIds() {
    return array_map(function ($element) {
      return $element['target_id'];
    }, $this->getEvent()->{EventManagerInterface::FIELD_REGISTRATION_TYPE}->getValue());
  }

  /**
   * {@inheritdoc}
   */
  public function getRegistrationTypes() {
    return $this->entityManager->getStorage('registration_type')->loadMultiple($this->getRegistrationTypeIds());
  }

  /**
   * {@inheritdoc}
   */
  public function registrationTypeIsValid(RegistrationTypeInterface $registration_type) {
    return in_array($registration_type->id(), $this->getRegistrationTypeIds());
  }

  /**
   * {@inheritdoc}
   */
  public function getCapacity() {
    $capacity = (int)$this->getEvent()->{EventManagerInterface::FIELD_CAPACITY}->value;
    if ($capacity != '' && is_numeric($capacity) && $capacity >= 0) {
      return $capacity;
    }
    return EventMetaInterface::CAPACITY_UNLIMITED;
  }

  /**
   * {@inheritdoc}
   */
  public function remainingCapacity() {
    $capacity = $this->getCapacity();
    if ($capacity == EventMetaInterface::CAPACITY_UNLIMITED) {
      return $capacity;
    }
    $remaining = $capacity - $this->countRegistrations();
    return $remaining > 0 ? $remaining : 0;
  }

  /**
   * {@inheritdoc}
   */
  function getDefaultGroups() {
    $groups = [];
    foreach ($this->getEvent()->{EventManagerInterface::FIELD_REGISTRATION_GROUPS} as $group) {
      $groups[] = $group->entity;
    }
    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  function buildQuery($entity_type) {
    return $this->entityManager->getStorage($entity_type)->getQuery('AND')
      ->condition('event__target_type', $this->getEvent()->getEntityTypeId(), '=')
      ->condition('event__target_id', $this->getEvent()->id(), '=');
  }

  /**
   * {@inheritdoc}
   */
  function buildRegistrationQuery() {
    return $this->buildQuery('registration');
  }

  /**
   * {@inheritdoc}
   */
  function getRegistrations() {
    $query = $this->buildRegistrationQuery();
    return $this->entityManager->getStorage('registration')->loadMultiple($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  function countRegistrations() {
    return $this->buildRegistrationQuery()->count()->execute();
  }

  /**
   * {@inheritdoc}
   */
  function buildRuleQuery() {
    return $this->buildQuery('rng_rule');
  }

  /**
   * {@inheritdoc}
   */
  function getRules($trigger = NULL) {
    $query = $this->buildRuleQuery();

    if ($trigger) {
      $query->condition('trigger_id', $trigger, '=');
    }

    return $this->entityManager->getStorage('rng_rule')->loadMultiple($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function trigger($trigger, $context = array()) {
    $context['event'] = $this->getEvent();
    foreach($this->getRules($trigger) as $rule) {
      foreach($rule->getActions() as $action) {
        // @todo: get contexts for $rule; ensure they exist on $context.
        $action->execute($context);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  function buildGroupQuery() {
    return $this->buildQuery('registration_group');
  }

  /**
   * {@inheritdoc}
   */
  function getGroups() {
    $query = $this->buildGroupQuery();
    return $this->entityManager->getStorage('registration_group')->loadMultiple($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  function buildRegistrantQuery() {
    return $this->entityManager->getStorage('registrant')->getQuery('AND')
      ->condition('identity__target_type', 'user', '=')
      ->condition('registration.entity.event__target_type', $this->getEvent()->getEntityTypeId(), '=')
      ->condition('registration.entity.event__target_id', $this->getEvent()->id(), '=');
  }

  /**
   * {@inheritdoc}
   */
  function getRegistrants() {
    $query = $this->buildRegistrantQuery();
    return $this->entityManager->getStorage('registrant')->loadMultiple($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  function countProxyIdentities() {
    $options = [
      'target_type' => 'user',
      'handler' => 'rng:register',
      'handler_settings' => ['event' => $this->getEvent()],
    ];
    $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
    return $handler->countReferenceableEntities();
  }

}