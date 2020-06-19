<?php

declare(strict_types = 1);

namespace Drupal\joinup_sparql;

use EasyRdf\GraphStore;

/**
 * Provides an interface for the 'joinup_sparql.graph_store.helper' service.
 */
interface JoinupSparqlGraphStoreHelperInterface {

  /**
   * Creates a new Graph Store object using the SPARQL connection.
   *
   * @return \EasyRdf\GraphStore
   *   The new graph store object.
   */
  public function createGraphStore(): GraphStore;

}
