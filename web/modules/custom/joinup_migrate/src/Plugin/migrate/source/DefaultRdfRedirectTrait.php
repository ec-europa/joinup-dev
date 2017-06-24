<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Implements RedirectImportInterface methods for RDF entities.
 *
 * @see \Drupal\joinup_migrate\RedirectImportInterface
 */
trait DefaultRdfRedirectTrait {

  use DefaultRedirectTrait {
    getRedirectSources as defaultGetRedirectSources;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectSources(Row $row) {
    $sources = $this->defaultGetRedirectSources($row);

    // Add also the canonical link of the source node.
    if ($nid = $row->getSourceProperty('nid')) {
      $sources[] = "node/$nid";
    }

    return $sources;
  }

}
