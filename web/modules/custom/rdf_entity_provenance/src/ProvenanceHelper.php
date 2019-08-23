<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity_provenance;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageInterface;

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
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface
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
  public function loadOrCreateEntityActivity(string $id): RdfInterface {
    return $this->loadOrCreateEntitiesActivity([$id])[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function loadOrCreateEntitiesActivity(array $ids): array {
    $activities = $this->loadProvenanceActivities($ids);
    // Shrink $ids list to the entities that are missing a provenance entry.
    $ids = array_diff($ids, array_keys($activities));
    foreach ($ids as $id) {
      $activities[$id] = $this->createProvenanceActivity($id);
    }
    return $activities;
  }

  /**
   * {@inheritdoc}
   */
  public function loadProvenanceActivity(string $id): ?RdfInterface {
    return $this->loadProvenanceActivities([$id])[$id] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadProvenanceActivities(array $ids): array {
    $provenance_activity = [];
    $provenance_ids = $this->getRdfStorage()->getQuery()
      ->condition('rid', 'provenance_activity')
      ->condition('provenance_entity', $ids, 'IN')
      ->execute();
    if ($provenance_ids) {
      /** @var \Drupal\rdf_entity\RdfInterface $activity */
      foreach ($this->getRdfStorage()->loadMultiple($provenance_ids) as $activity) {
        $provenance_activity[$activity->get('provenance_entity')->value] = $activity;
      }
    }

    return $provenance_activity;
  }

  /**
   * {@inheritdoc}
   */
  public function loadActivityAssociatedWith(RdfInterface $entity): ?string {
    $associated_id = $entity->get('provenance_associated_with')->value;
    if (empty($associated_id)) {
      return NULL;
    }

    $entity = $this->getRdfStorage()->load($associated_id);
    return $entity ? $entity->toUrl('canonical', ['absolute' => TRUE])->toString() : NULL;
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
   * @return \Drupal\sparql_entity_storage\SparqlEntityStorageInterface
   *   The RDF entity storage.
   */
  protected function getRdfStorage(): SparqlEntityStorageInterface {
    if (!isset($this->rdfStorage)) {
      $this->rdfStorage = $this->entityTypeManager->getStorage('rdf_entity');
    }
    return $this->rdfStorage;
  }

}
