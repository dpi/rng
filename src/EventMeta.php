<?php

namespace Drupal\rng;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
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
   * @var \Drupal\Core\Entity\EntityInterface
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
   * The RNG configuration service.
   *
   * @var \Drupal\rng\RngConfigurationInterface
   */
  protected $rngConfiguration;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new EventMeta object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_plugin_manager
   *   The selection plugin manager.
   * @param \Drupal\courier\Service\IdentityChannelManagerInterface $identity_channel_manager
   *   The identity channel manager.
   * @param \Drupal\rng\RngConfigurationInterface $rng_configuration
   *   The RNG configuration service.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The event entity.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactoryInterface $config_factory, SelectionPluginManagerInterface $selection_plugin_manager, IdentityChannelManagerInterface $identity_channel_manager, RngConfigurationInterface $rng_configuration, EventManagerInterface $event_manager, EntityInterface $entity) {
    $this->entityManager = $entity_manager;
    $this->configFactory = $config_factory;
    $this->selectionPluginManager = $selection_plugin_manager;
    $this->identityChannelManager = $identity_channel_manager;
    $this->rngConfiguration = $rng_configuration;
    $this->eventManager = $event_manager;
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
      $container->get('rng.configuration'),
      $container->get('rng.event_manager'),
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
  public function getEventType() {
    return $this->eventManager->eventType($this->entity->getEntityTypeId(), $this->entity->bundle());
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
  public function getRegistrantsMinimum() {
    if (isset($this->getEvent()->{EventManagerInterface::FIELD_REGISTRATION_REGISTRANTS_MINIMUM})) {
      $field = $this->getEvent()->{EventManagerInterface::FIELD_REGISTRATION_REGISTRANTS_MINIMUM};
      $minimum = $field->value;
      if ($minimum !== '' && is_numeric($minimum) && $minimum >= 0) {
        return $minimum;
      }
    }
    return 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegistrantsMaximum() {
    if (isset($this->getEvent()->{EventManagerInterface::FIELD_REGISTRATION_REGISTRANTS_MAXIMUM})) {
      $field = $this->getEvent()->{EventManagerInterface::FIELD_REGISTRATION_REGISTRANTS_MAXIMUM};
      $maximum = $field->value;
      if ($maximum !== '' && is_numeric($maximum) && $maximum >= 0) {
        return $maximum;
      }
    }
    return EventMetaInterface::CAPACITY_UNLIMITED;
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
    $rules = [];

    if ($trigger != 'rng_event.register') {
      return $rules;
    }

    /** @var \Drupal\rng\EventTypeRuleInterface[] $default_rules */
    $default_rules = $this
      ->entityManager
      ->getStorage('rng_event_type_rule')
      ->loadByProperties([
        'entity_type' => $this->getEvent()->getEntityTypeId(),
        'bundle' => $this->getEvent()->bundle(),
        'trigger' => $trigger,
      ]);

    foreach ($default_rules as $default_rule) {
      $rule = Rule::create([
        'event' => array('entity' => $this->getEvent()),
        'trigger_id' => $trigger,
        'status' => TRUE,
      ]);

      foreach ($default_rule->getConditions() as $condition) {
        $plugin_id = $condition['id'];
        unset($condition['id']);
        $component = RuleComponent::create()
          ->setType('condition')
          ->setPluginId($plugin_id)
          ->setConfiguration($condition);
        $rule->addComponent($component);
      }

      foreach ($default_rule->getActions() as $action) {
        $component = RuleComponent::create()
          ->setType('action')
          ->setPluginId($action['id'])
          ->setConfiguration($action['configuration']);
        $rule->addComponent($component);
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
  public function canRegisterProxyIdentities() {
    // Create is checked first since it is usually the cheapest.
    $identity_types = $this->getCreatableIdentityTypes();
    foreach ($identity_types as $entity_type_id => $bundles) {
      $accessControl = $this->entityManager->getAccessControlHandler($entity_type_id);
      if ($this->entityTypeHasBundles($entity_type_id)) {
        foreach ($bundles as $bundle) {
          if ($accessControl->createAccess($bundle)) {
            return TRUE;
          }
        }
      }
      elseif (!empty($bundles)) {
        if ($accessControl->createAccess()) {
          return TRUE;
        }
      }
    }

    // Reference existing.
    $identity_types = $this->getIdentityTypes();
    foreach ($identity_types as $entity_type_id => $bundles) {
      $referencable_bundles = $this->entityTypeHasBundles($entity_type_id) ? $bundles : [];
      $count = $this->countRngReferenceableEntities($entity_type_id, $referencable_bundles);
      if ($count > 0) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function countProxyIdentities() {
    $total = 0;

    foreach ($this->getIdentityTypes() as $entity_type_id => $bundles) {
      if ($this->entityTypeHasBundles($entity_type_id)) {
        $total += $this->countRngReferenceableEntities($entity_type_id, $bundles);
      }
      elseif (!empty($bundles)) {
        $total += $this->countRngReferenceableEntities($entity_type_id);
      }
    }

    return $total;
  }

  /**
   * Count referencable entities using a rng_register entity selection plugin.
   *
   * @param string $entity_type_id
   *   An identity entity type ID.
   * @param array $bundles
   *   (optional) An array of bundles.
   *
   * @return integer
   *   The number of referencable entities.
   */
  protected function countRngReferenceableEntities($entity_type_id, $bundles = []) {
    $selection_groups = $this->selectionPluginManager
      ->getSelectionGroups($entity_type_id);

    if (isset($selection_groups['rng_register'])) {
      $options = [
        'target_type' => $entity_type_id,
        'handler' => 'rng_register',
        'handler_settings' => [
          'event_entity_type' => $this->getEvent()->getEntityTypeId(),
          'event_entity_id' => $this->getEvent()->id(),
        ],
      ];

      if (!empty($bundles)) {
        $options['handler_settings']['target_bundles'] = $bundles;
      }

      return $this->selectionPluginManager
        ->getInstance($options)
        ->countReferenceableEntities();
    }

    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentityTypes() {
    $event_type = $this->getEventType();

    $result = [];
    $identity_types_available = $this->rngConfiguration->getIdentityTypes();
    foreach ($identity_types_available as $entity_type_id) {
      $bundles = $this->entityManager->getBundleInfo($entity_type_id);
      foreach ($bundles as $bundle => $info) {
        if ($event_type->canIdentityTypeReference($entity_type_id, $bundle)) {
          $result[$entity_type_id][] = $bundle;
        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatableIdentityTypes() {
    $event_type = $this->getEventType();

    $result = [];
    $identity_types_available = $this->rngConfiguration->getIdentityTypes();
    foreach ($identity_types_available as $entity_type_id) {
      $bundles = $this->entityManager->getBundleInfo($entity_type_id);
      foreach ($bundles as $bundle => $info) {
        if ($event_type->canIdentityTypeCreate($entity_type_id, $bundle)) {
          $result[$entity_type_id][] = $bundle;
        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function identitiesCanRegister($entity_type, array $entity_ids) {
    $identity_types = $this->getIdentityTypes();
    if (isset($identity_types[$entity_type])) {
      $options = [
        'target_type' => $entity_type,
        'handler' => 'rng_register',
        'handler_settings' => [
          'event_entity_type' => $this->getEvent()->getEntityTypeId(),
          'event_entity_id' => $this->getEvent()->id(),
        ],
      ];

      if ($this->entityTypeHasBundles($entity_type)) {
        $options['handler_settings']['target_bundles'] = $identity_types[$entity_type];
      }

      /* @var $selection \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface */
      $selection = $this->selectionPluginManager->getInstance($options);
      return $selection->validateReferenceableEntities($entity_ids);
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

  /**
   * Determine whether an entity type uses a separate bundle entity type.
   *
   * @param string $entity_type_id
   *   An entity type Id.
   *
   * @return boolean
   *   Whether an entity type uses a separate bundle entity type.
   */
  protected function entityTypeHasBundles($entity_type_id) {
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    return ($entity_type->getBundleEntityType() !== NULL);
  }

}
