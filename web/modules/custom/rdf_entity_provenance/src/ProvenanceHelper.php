<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity_provenance;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rdf_entity\RdfEntitySparqlStorageInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Provides helper methods to fetch, check and update provenance data.
 */
class ProvenanceHelper implements ProvenanceHelperInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The RDF entity storage.
   *
   * @var \Drupal\rdf_entity\RdfEntitySparqlStorageInterface
   */
  protected $rdfStorage;

  /**
   * Constructs the ProvenanceHelper service object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvenanceByReferredEntity(string $id): RdfInterface {
    if (!$activity = $this->loadProvenanceActivity($id)) {
      $activity = $this->createProvenanceActivity($id);
    }

    return $activity;
  }

  /**
   * {@inheritdoc}
   */
  public function loadProvenanceActivity(string $id): ?RdfInterface {
    /** @var \Drupal\rdf_entity\RdfInterface[] $activities */
    $activities = $this->getStorage()->loadByProperties([
      'rid' => 'provenance_activity',
      'provenance_entity' => $id,
    ]);

    return empty($activities) ? NULL : reset($activities);
  }

  /**
   * Creates a provenance activity for the passed rdf_entity.
   *
   * @param string $id
   *   The rdf entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The provenance activity.
   */
  protected function createProvenanceActivity(string $id): RdfInterface {
    /** @var \Drupal\rdf_entity\RdfInterface $activity */
    $activity = $this->getRdfStorage()->create([
      'rid' => 'provenance_activity',
      'provenance_entity' => $id,
    ]);

    return $activity;
  }

  /**
   * Returns the RDF entity storage.
   *
   * @return \Drupal\rdf_entity\RdfEntitySparqlStorageInterface
   *   The RDF entity storage.
   */
  protected function getRdfStorage(): RdfEntitySparqlStorageInterface {
    if (!isset($this->rdfStorage)) {
      $this->rdfStorage = $this->entityTypeManager->getStorage('rdf_entity');
    }
    return $this->rdfStorage;
  }

}
