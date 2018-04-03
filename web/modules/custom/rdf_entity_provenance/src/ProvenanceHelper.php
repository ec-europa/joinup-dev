<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity_provenance;

use Drupal\Core\Entity\EntityTypeManagerInterface;
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
  public function isFederated(RdfInterface $rdf_entity): bool{
    return empty($this->getProvenanceActivity($rdf_entity));
  }

  /**
   * {@inheritdoc}
   */
  public function getProvenanceActivity(RdfInterface $rdf_entity): RdfInterface {
    if (!$activity = $this->loadProvenanceActivity($rdf_entity)) {
      $activity = $this->createProvenanceActivity($rdf_entity);
    }

    return $activity;
  }

  /**
   * {@inheritdoc}
   */
  public function loadProvenanceActivity(RdfInterface $rdf_entity): ?RdfInterface {
    /** @var RdfInterface $activity */
    $activity = $this->getStorage()->loadByProperties([
      'bundle' => 'provenance_activity',
      'provenance_entity' => $rdf_entity->id(),
    ]);

    return $activity;
  }

  /**
   * Creates a provenance activity for the passed rdf_entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The rdf entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The provenance activity.
   */
  protected function createProvenanceActivity(RdfInterface $rdf_entity): RdfInterface {
    /** @var RdfInterface $activity */
    $activity = $this->getStorage()->create([
      'bundle' => 'provenance_activity',
      'provenance_entity' => $rdf_entity->id(),
    ]);

    return $activity;
  }

  /**
   * Retrieves the rdf_entity storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage.
   */
  protected function getStorage() {
    return $this->entityTypeManager->getStorage('rdf_entity');
  }

}