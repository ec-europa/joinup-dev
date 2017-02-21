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
   * Statically cache the results.
   *
   * @var string[]
   */
  protected $cache = [];

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      return NULL;
    }
    if (!isset($this->cache[$this->configuration['vocabulary']][$value])) {
      /** @var \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage $storage */
      $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      if (!$terms = $storage->loadByProperties([
        'name' => (array) $value,
        'vid' => $this->configuration['vocabulary'],
      ])
      ) {
        $migrate_executable->saveMessage("Term '$value' does not exits in destination.");
        return NULL;
      }
      $this->cache[$this->configuration['vocabulary']][$value] = array_keys($terms)[0];
    }
    return $this->cache[$this->configuration['vocabulary']][$value];
  }

}
