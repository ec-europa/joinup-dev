<?php

namespace Drupal\joinup_migrate\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Row;
use Drupal\rdf_entity\Exception\DuplicatedIdException;

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

    $id = $row->getDestinationProperty('id');
    if ($id && empty($old_destination_id_values) && $this->storage->idExists($id)) {
      throw new DuplicatedIdException("ID '$id' already taken.");
    }

    return parent::getEntity($row, $old_destination_id_values);
  }

}
