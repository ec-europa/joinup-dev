<?php

namespace Drupal\joinup_federation\Plugin\Validation\Constraint;

use Drupal\Core\Entity\EntityInterface;

/**
 * Reusable code for Joinup entity reference constraint validators.
 */
trait JoinupEntityReferenceConstraintTrait {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The staging candidate graphs service.
   *
   * @var \Drupal\joinup_federation\StagingCandidateGraphsInterface
   */
  protected $stagingCandidateGraphs;

  /**
   * Loads the existing, unchanged host entity.
   *
   * This method checks if the host entity is an RDF entity. If so, it passes
   * the host entity graph to SparqlEntityStorageInterface::loadUnchanged().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The host entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The unchanged entity.
   */
  protected function loadUnchanged(EntityInterface $entity): EntityInterface {
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    if ($entity->getEntityTypeId() === 'rdf_entity') {
      /** @var \Drupal\rdf_entity\RdfInterface $entity */
      /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $storage */
      return $storage->loadUnchanged($entity->id(), [$entity->get('graph')->target_id]);
    }
    return $storage->loadUnchanged($entity->id());
  }

}
