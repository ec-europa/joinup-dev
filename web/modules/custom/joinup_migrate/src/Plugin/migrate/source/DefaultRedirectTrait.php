<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Entity\EntityInterface;
use Drupal\migrate\Row;

/**
 * Default implementation of RedirectImportInterface methods.
 *
 * @see \Drupal\joinup_migrate\RedirectImportInterface
 */
trait DefaultRedirectTrait {

  /**
   * {@inheritdoc}
   */
  public function getRedirectSources(Row $row) {
    $sources = [];
    $nid = (int) $row->getSourceProperty('nid');

    // @see https://api.drupal.org/api/drupal/includes%21path.inc/function/drupal_lookup_path/6.x
    $sql = "SELECT dst FROM {url_alias} WHERE language IN ('', 'en') AND src = :src ORDER BY pid DESC";
    if ($path = $this->getDatabase()->queryRange($sql, 0, 1, [':src' => "node/$nid"])->fetchField()) {
      $sources[] = $path;
    }

    return $sources;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUri(EntityInterface $entity) {
    return 'internal:/' . $entity->toUrl()->getInternalPath();
  }

}
