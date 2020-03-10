<?php

declare(strict_types = 1);

namespace Drupal\joinup_sparql;

use Drupal\sparql_entity_storage\SparqlGraphStoreTrait;
use EasyRdf\GraphStore;

/**
 * Default implementation for the 'joinup_sparql.graph_store.helper' service.
 */
class JoinupSparqlGraphStoreHelper implements JoinupSparqlGraphStoreHelperInterface {

  use SparqlGraphStoreTrait {
    createGraphStore as traitCreateGraphStore;
  }

  /**
   * {@inheritdoc}
   */
  public function createGraphStore(): GraphStore {
    return $this->traitCreateGraphStore();
  }

}
