<?php

/**
 * @file
 * Contains \Drupal\rng\Entity\Rule.
 */

namespace Drupal\rng\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\rng\RuleInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Utility\String;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the event rule entity.
 *
 * @ContentEntityType(
 *   id = "rng_rule",
 *   label = @Translation("Event Rule"),
 *   base_table = "rng_rule",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   handlers = {
 *     "access" = "Drupal\rng\AccessControl\EventAccessControlHandler",
 *     "list_builder" = "\Drupal\rng\Lists\RuleListBuilder",
 *     "form" = {
 *       "delete" = "Drupal\rng\Form\RuleDeleteForm",
 *     },
 *   },
 *   admin_permission = "administer rng",
 *   links = {
 *     "delete-form" = "/rng/rule/{rng_rule}/delete"
 *   }
 * )
 */
class Rule extends ContentEntityBase implements RuleInterface {

  /**
   * {@inheritdoc}
   */
  public function getEvent() {
    return $this->get('event')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTriggerID() {
    return $this->get('trigger_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $action_ids = \Drupal::entityQuery('rng_action')
      ->condition('rule', $this->id(), '=')
      ->execute();
    entity_delete_multiple('rng_action', $action_ids);
    parent::delete();
  }

  /**
   * {@inheritdoc}
   */
  public function getConditions() {
    $action_ids = \Drupal::entityQuery('rng_action')
      ->condition('rule', $this->id(), '=')
      ->condition('type', 'condition', '=')
      ->execute();
    return entity_load_multiple('rng_action', $action_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getActions() {
    $action_ids = \Drupal::entityQuery('rng_action')
      ->condition('rule', $this->id(), '=')
      ->condition('type', 'action', '=')
      ->execute();
    return entity_load_multiple('rng_action', $action_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluateConditions($context_values = []) {
    // @todo: move evaluation and context to Action/Condition hybrid entity when
    // @todo:   action plugins get better context integration.
    $success = 0;
    $count = 0; // Counts successfully loaded condition plugins.
    $conditions = $this->getConditions();
    foreach ($conditions as $condition_storage) {
      if (($condition = $condition_storage->createInstance()) !== NULL) {
        $count++;
      }

      // Add all context to the conditions
      foreach ($condition->getContextDefinitions() as $name => $context) {
        $data_type = $context->getDataType();
        if (isset($context_values[$data_type])) {
          $condition->setContextValue($name, $context_values[$data_type]);
        }
        else if ($context->isRequired()) {
          throw new ContextException(String::format("Missing context @type for condition @plugin_id on rule #@rule_id.", array('@type' => $data_type, '@plugin_id' => $condition_storage->getPluginId(), '@rule_id' => $this->id())));
        }
      }

      if ($condition->evaluate()) {
        $success++;
      }
      else {
        // Cancel evaluating remaining conditions
        return FALSE;
      }
    }

    // Will fail if there are no conditions.
    return $count && ($success == count($conditions)) && ($count == count($conditions));
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Rule ID'))
      ->setDescription(t('The rule ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['event'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Event'))
      ->setDescription(t('Select event to associate with this rule.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['trigger_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Trigger'))
      ->setDescription(t('The trigger ID for this rule.'))
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time that the rule was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The last time the rule was edited.'));

    return $fields;
  }

}