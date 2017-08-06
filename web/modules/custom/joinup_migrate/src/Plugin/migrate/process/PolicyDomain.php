<?php

namespace Drupal\joinup_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a processor for policy domain term references.
 *
 * @MigrateProcessPlugin(
 *   id = "policy_domain"
 * )
 */
class PolicyDomain extends ProcessPluginBase {

  /**
   * Statically cache the results.
   *
   * @var string[]
   */
  protected static $cache = [];

  /**
   * Taxonomy term storage.
   *
   * @var \Drupal\rdf_taxonomy\TermRdfStorage
   */
  protected $termStorage;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      return NULL;
    }

    if (!isset(static::$cache[$value])) {
      $terms = $this->getTermStorage()->loadByProperties([
        'name' => $value,
        'vid' => 'policy_domain',
      ]);
      foreach ($terms as $tid => $term) {
        if ($this->getTermStorage()->loadParents($tid)) {
          // We stop on the first term with parents (AKA is 2nd level term').
          static::$cache[$value] = $tid;
          break;
        }
      }
    }
    if (!isset(static::$cache[$value])) {
      $migrate_executable->saveMessage("Term '$value' missed from D8 vocabulary 'Policy domain' (policy_domain).");
      return NULL;
    }

    return static::$cache[$value];
  }

  /**
   * Returns the RDF taxonomy term storage.
   *
   * @return \Drupal\rdf_taxonomy\TermRdfStorage
   */
  protected function getTermStorage() {
    if (!isset($this->termStorage)) {
      $this->termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    }
    return $this->termStorage;
  }

}
