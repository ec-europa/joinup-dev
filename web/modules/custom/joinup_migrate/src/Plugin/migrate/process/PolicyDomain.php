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
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      return NULL;
    }

    if (!isset(static::$cache[$value])) {
      /** @var \Drupal\rdf_taxonomy\TermRdfStorage $storage */
      $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $terms = $storage->loadByProperties([
        'name' => (array) $value,
        'vid' => 'policy_domain',
      ]);
      foreach ($terms as $tid => $term) {
        if ($storage->loadParents($tid)) {
          // We stop on the first term with parents (AKA 'is 2nd level term').
          static::$cache[$value] = $tid;
          break;
        }
      }
    }
    if (!isset(static::$cache[$value])) {
      $migrate_executable->saveMessage("Term '$value' does not exits in destination.");
      return NULL;
    }

    return static::$cache[$value];
  }

}
