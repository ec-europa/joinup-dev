<?php

namespace Drupal\joinup_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\taxonomy\TermInterface;

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
      /** @var \Drupal\rdf_taxonomy\TermRdfStorage $storage */
      $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      /** @var \Drupal\taxonomy\TermInterface[] $terms */
      $terms = $storage->loadByProperties([
        'name' => (array) $value,
        'vid' => $this->configuration['vocabulary'],
      ]);

      if (!empty($this->configuration['top_level_only'])) {
        // Allow only top level terms.
        $terms = array_filter($terms, function (TermInterface $term) use ($storage) {
          return (bool) !$storage->loadParents($term->id());
        });
      }

      if (!$terms) {
        $migrate_executable->saveMessage("Term '$value' does not exits in destination.");
        return NULL;
      }
      $this->cache[$this->configuration['vocabulary']][$value] = array_keys($terms)[0];
    }
    return $this->cache[$this->configuration['vocabulary']][$value];
  }

}
