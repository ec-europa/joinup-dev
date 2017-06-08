<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Implements \Drupal\joinup_migrate\RedirectImportInterface methods for nodes.
 */
trait DefaultNodeRedirectTrait {

  /**
   * {@inheritdoc}
   */
  public function getRedirectSource(Row $row) {
    $nid = (int) $row->getSourceProperty('nid');

    // @see https://api.drupal.org/api/drupal/includes%21path.inc/function/drupal_lookup_path/6.x
    $sql = "SELECT dst FROM {url_alias} WHERE language IN ('', 'en') AND src = :src ORDER BY pid DESC";
    $path = $this->getDatabase()->queryRange($sql, 0, 1, [':src' => "node/$nid"])->fetchField();
    if (!$path) {
      return NULL;
    }

    return ['path' => $path, 'query' => NULL];
  }

}
