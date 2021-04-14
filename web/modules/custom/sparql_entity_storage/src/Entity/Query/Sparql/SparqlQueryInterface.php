<?php

namespace Drupal\sparql_entity_storage\Entity\Query\Sparql;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageInterface;

/**
 * Provides an interface for SPARQL entity queries.
 */
interface SparqlQueryInterface extends QueryInterface {

  /**
   * Sets the IDs of the graph to be queried.
   *
   * If this method is not called, the default graphs for this entity type are
   * used. Calling the method with no argument will remove any filtering of
   * SPARQL entities on graphs and the query will return all entities from all
   * graphs that are known by Drupal for this entity type.
   *
   * @param string[]|null $graph_ids
   *   (optional) A list of graph IDs to filter on. If omitted, the query will
   *   return all entities from all graphs that are known by Drupal for this
   *   entity type.
   *
   * @return $this
   */
  public function graphs(array $graph_ids = NULL): self;

  /**
   * Returns the entity type.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type object.
   */
  public function getEntityType(): EntityTypeInterface;

  /**
   * Returns the entity type storage.
   *
   * @return \Drupal\sparql_entity_storage\SparqlEntityStorageInterface
   *   The entity type storage.
   */
  public function getEntityStorage(): SparqlEntityStorageInterface;

}
