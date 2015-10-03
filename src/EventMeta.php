<?php

/**
 * @file
 * Contains \Drupal\rng\EventMeta.
 */

namespace Drupal\rng;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\courier\Service\IdentityChannelManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\rng\Entity\Rule;
use Drupal\rng\Entity\RuleComponent;

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
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The selection plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionPluginManager;

  /**
   * The identity channel manager.
   *
   * @var \Drupal\courier\Service\IdentityChannelManagerInterface
   */
  protected $identityChannelManager;

  /**
   * Constructs a new EventMeta object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_plugin_manager
   *   The selection plugin manager.
   * @param \Drupal\courier\Service\IdentityChannelManagerInterface $identity_channel_manager
   *   The identity channel manager.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The event entity.
   */
  public function __construct(EntityManager $entity_manager, ConfigFactoryInterface $config_factory, SelectionPluginManagerInterface $selection_plugin_manager, IdentityChannelManagerInterface $identity_channel_manager, EntityInterface $entity) {
    $this->entityManager = $entity_manager;
    $this->configFactory = $config_factory;
    $this->selectionPluginManager = $selection_plugin_manager;
    $this->identityChannelManager = $identity_channel_manager;
    $this->entity = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityInterface $entity) {
    return new static(
      $container->get('entity.manager'),
      $container->get('config.factory'),
      $container->get('plugin.manager.entity_reference_selection'),
      $container->get('plugin.manager.identity_channel'),
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
  public function getReplyTo() {
    return $this->getEvent()->{EventManagerInterface::FIELD_EMAIL_REPLY_TO}->value;
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
  public function removeRegistrationType($registration_type_id) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $registration_types */
    $registration_types = &$this->entity->{EventManagerInterface::FIELD_REGISTRATION_TYPE};
    foreach ($registration_types->getValue() as $key => $value) {
      if ($value['target_id'] == $registration_type_id) {
        $registration_types->removeItem($key);
      }
    }
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function removeGroup($group_id) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $groups */
    $groups = &$this->entity->{EventManagerInterface::FIELD_REGISTRATION_GROUPS};
    foreach ($groups->getValue() as $key => $value) {
      if ($value['target_id'] == $group_id) {
        $groups->removeItem($key);
      }
    }
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getCapacity() {
    $capacity = (int) $this->getEvent()->{EventManagerInterface::FIELD_CAPACITY}->value;
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
  function getRules($trigger = NULL, $defaults = FALSE, $is_active = TRUE) {
    $query = $this->buildRuleQuery();

    if ($trigger) {
      $query->condition('trigger_id', $trigger, '=');
    }

    if (isset($is_active)) {
      $query->condition('status', $is_active, '=');
    }

    $rules = $this->entityManager
      ->getStorage('rng_rule')
      ->loadMultiple($query->execute());
    if ($defaults && !$rules) {
      return $this->getDefaultRules($trigger);
    }

    return $rules;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRules($trigger = NULL) {
    $definitions = [];
    if ($trigger == 'rng_event.register') {
      // Allow any user to create a registration on the event.
      $definitions['user_role']['condition']['rng_user_role'] = ['roles' => []];
      $definitions['user_role']['action']['registration_operations'] = ['operations' => ['create' => TRUE]];

      // Allow registrants to edit their registrations.
      $definitions['registrant']['condition']['rng_registration_identity'] = [];
      $definitions['registrant']['action']['registration_operations'] = [
        'operations' => [
          'view' => TRUE,
          'update' => TRUE
        ]
      ];

      // Give event managers all rights.
      $definitions['event_operation']['condition']['rng_event_operation'] = ['operations' => ['manage event' => TRUE]];
      $definitions['event_operation']['action']['registration_operations'] = [
        'operations' => [
          'create' => TRUE,
          'view' => TRUE,
          'update' => TRUE,
          'delete' => TRUE
        ]
      ];
    }

    $rules = [];
    foreach ($definitions as $definition) {
      $rule = Rule::create([
        'event' => array('entity' => $this->getEvent()),
        'trigger_id' => 'rng_event.register',
        'status' => TRUE,
      ]);
      foreach (['condition', 'action'] as $component_type) {
        if (isset($definition[$component_type])) {
          foreach ($definition[$component_type] as $plugin_id => $configuration) {
            $component = RuleComponent::create()
              ->setType($component_type)
              ->setPluginId($plugin_id)
              ->setConfiguration($configuration);
            $rule->addComponent($component);
          }
        }
      }

      $rules[] = $rule;
    }

    return $rules;
  }

  /**
   * {@inheritdoc}
   */
  function isDefaultRules($trigger) {
    return (boolean) !$this->getRules($trigger);
  }

  /**
   * {@inheritdoc}
   */
  public function trigger($trigger, $context = array()) {
    $context['event'] = $this->getEvent();
    foreach ($this->getRules($trigger) as $rule) {
      if ($rule->evaluateConditions()) {
        foreach ($rule->getActions() as $action) {
          $action->execute($context);
        }
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
  public function buildRegistrantQuery($entity_type_id = NULL) {
    $query = $this->entityManager->getStorage('registrant')->getQuery('AND')
      ->condition('registration.entity.event__target_type', $this->getEvent()->getEntityTypeId(), '=')
      ->condition('registration.entity.event__target_id', $this->getEvent()->id(), '=');

    if ($entity_type_id) {
      $query->condition('identity__target_type', $entity_type_id, '=');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegistrants($entity_type_id = NULL) {
    $query = $this->buildRegistrantQuery($entity_type_id);
    return $this->entityManager->getStorage('registrant')->loadMultiple($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function countProxyIdentities() {
    $total = 0;

    foreach ($this->getIdentityTypes() as $entity_type_id) {
      $count = $this
        ->selectionPluginManager
        ->getInstance([
          'target_type' => $entity_type_id,
          'handler' => 'rng_register',
          'handler_settings' => ['event_entity_type' => $this->getEvent()->getEntityTypeId(), 'event_entity_id' => $this->getEvent()->id()],
        ])
        ->countReferenceableEntities();
      if (is_numeric($count)) {
        $total += $count;
      }
    }

    return $total;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentityTypes() {
    $config = $this->configFactory->get('rng.settings');
    $identity_types = $config->get('identity_types');
    $allowed_identity_types = is_array($identity_types) ? $identity_types : [];
    $available_identity_types = $this->identityChannelManager->getIdentityTypes();
    return array_intersect($allowed_identity_types, $available_identity_types);
  }

  /**
   * {@inheritdoc}
   */
  public function identitiesCanRegister($entity_type, array $entity_ids) {
    if (in_array($entity_type, $this->getIdentityTypes())) {
      if ($this->duplicateRegistrantsAllowed()) {
        return $entity_ids;
      }
      else {
        $options = [
          'target_type' => $entity_type,
          'handler' => 'rng_register',
          'handler_settings' => [
            'event_entity_type' => $this->getEvent()->getEntityTypeId(),
            'event_entity_id' => $this->getEvent()->id(),
          ],
        ];

        /* @var $selection \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface */
        $selection = $this->selectionPluginManager->getInstance($options);
        return $selection->validateReferenceableEntities($entity_ids);
      }
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  function addDefaultAccess() {
    $rules = $this->getDefaultRules('rng_event.register');
    foreach ($rules as $rule) {
      $rule->save();
    }
  }

}
