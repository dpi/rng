<?php

namespace Drupal\rng;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the schema for Registrant entities.
 */
class RegistrantStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    if ($storage_definition->getName() == 'type') {
      // This field was added to the registrant entity in a hook_update_N
      // Since you cannot specifiy an initial value when installing a field,
      // and there previously was only a 'registrant' bundle, then use this
      // default value.
      // @see https://www.drupal.org/node/2346019#comment-11746237
      $schema['fields']['type']['initial'] = 'registrant';
    }
    return $schema;
  }

}
