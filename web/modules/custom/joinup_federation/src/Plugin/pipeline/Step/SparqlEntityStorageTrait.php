<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\rdf_entity\Entity\Query\Sparql\SparqlQueryInterface;
use Drupal\rdf_entity\RdfEntitySparqlStorageInterface;

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
   * @var \Drupal\rdf_entity\RdfEntitySparqlStorageInterface
   */
  protected $rdfStorage;

  /**
   * The cached SPARQL entity query.
   *
   * @var \Drupal\rdf_entity\Entity\Query\Sparql\SparqlQueryInterface
   */
  protected $sparqlQuery;

  /**
   * Returns the RDF storage.
   *
   * @return \Drupal\rdf_entity\RdfEntitySparqlStorageInterface
   *   The RDF storage.
   */
  protected function getRdfStorage(): RdfEntitySparqlStorageInterface {
    if (!isset($this->rdfStorage)) {
      $this->rdfStorage = $this->entityTypeManager->getStorage('rdf_entity');
    }
    return $this->rdfStorage;
  }

  /**
   * Returns the SPARQL entity query.
   *
   * @return \Drupal\rdf_entity\Entity\Query\Sparql\SparqlQueryInterface
   *   The entity query.
   */
  protected function getSparqlQuery(): SparqlQueryInterface {
    if (!isset($this->sparqlQuery)) {
      $this->sparqlQuery = $this->getRdfStorage()->getQuery();
    }
    return $this->sparqlQuery;
  }

}
