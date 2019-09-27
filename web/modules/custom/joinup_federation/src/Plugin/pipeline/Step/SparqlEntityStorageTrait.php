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
    /** @var \Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlQueryInterface $query */
    $query = $this->getRdfStorage()->getQuery();
    return $query;
  }

}
