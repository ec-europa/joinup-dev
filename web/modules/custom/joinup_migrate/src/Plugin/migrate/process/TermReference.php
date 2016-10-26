<?php

namespace Drupal\joinup_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a processor for Joinup Rdf term references.
 *
 * @MigrateProcessPlugin(
 *   id = "term_reference"
 * )
 */
class TermReference extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    /** @var \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    if (!$terms = $storage->loadByProperties(['name' => $value])) {
      $migrate_executable->saveMessage("Term '$value' does not exits in destination.");
    }
    return array_shift(array_keys($terms));
  }

}
