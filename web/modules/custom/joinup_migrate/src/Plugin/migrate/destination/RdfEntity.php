<?php

namespace Drupal\joinup_migrate\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Row;

/**
 * Provides destination plugin for RDF entities.
 *
 * @MigrateDestination(
 *   id = "entity:rdf_entity"
 * )
 */
class RdfEntity extends EntityContentBase {

  /**
   * {@inheritdoc}
   */
  public function getEntity(Row $row, array $old_destination_id_values) {
    if ($row->isStub()) {
      return parent::getEntity($row, $old_destination_id_values);
    }

    // Migrations that are updating existing entities should explicitly declare
    // this in the destination configuration. Otherwise, the destination plugin
    // is not able to distinguish between a new entity with a pre-filled ID and
    // an existing entity.
    $update_existing = !empty($this->configuration['update_existing']);

    $id_key = $this->getKey('id');
    $id = $row->getDestinationProperty($id_key);
    if (!$update_existing && $id && empty($old_destination_id_values) && $this->storage->idExists($id)) {
      // This ID has been already taken.
      $row->setDestinationProperty($id_key, NULL);
    }

    return parent::getEntity($row, $old_destination_id_values);
  }

}
