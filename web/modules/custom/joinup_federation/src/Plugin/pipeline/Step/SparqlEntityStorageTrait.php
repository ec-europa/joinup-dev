<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlQueryInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageInterface;

/**
 * Utility trait concerning SPARQL storage and entity query.
 */
trait SparqlEntityStorageTrait {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The RDF entity SPARQL storage.
   *
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface
   */
  protected $rdfStorage;

  /**
   * The cached SPARQL entity query.
   *
   * @var \Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlQueryInterface
   */
  protected $sparqlQuery;

  /**
   * Returns the RDF storage.
   *
   * @return \Drupal\sparql_entity_storage\SparqlEntityStorageInterface
   *   The RDF storage.
   */
  protected function getRdfStorage(): SparqlEntityStorageInterface {
    if (!isset($this->rdfStorage)) {
      $this->rdfStorage = $this->entityTypeManager->getStorage('rdf_entity');
    }
    return $this->rdfStorage;
  }

  /**
   * Returns the SPARQL entity query.
   *
   * @return \Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlQueryInterface
   *   The entity query.
   */
  protected function getSparqlQuery(): SparqlQueryInterface {
    if (!isset($this->sparqlQuery)) {
      $this->sparqlQuery = $this->getRdfStorage()->getQuery();
    }
    return $this->sparqlQuery;
  }

  /**
   * Returns the ids of solutions in the staging graph.
   *
   * @return array
   *   And array of ids.
   */
  protected function getIncomingSolutionIds(): array {
    return $this->getSparqlQuery()
      ->graphs(['staging'])
      ->condition('rid', 'solution')
      ->notExists('field_is_version_of')
      ->execute();
  }

  /**
   * Returns a list of entity ids existing in the sink graph.
   *
   * @return array
   *   An array of entity ids.
   */
  protected function getAllIncomingIds(): array {
    $results = $this->sparql->query("SELECT DISTINCT(?entityId) WHERE { GRAPH <{$this->getGraphUri('sink')}> { ?entityId ?p ?o . } }");
    $all_imported_ids = array_map(function (\stdClass $item): string {
      return $item->entityId->getUri();
    }, $results->getArrayCopy());

    return $all_imported_ids;
  }

}
