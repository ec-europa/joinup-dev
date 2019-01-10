<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Overrides the core class to provide support for the 'staging' graph.
 */
class RdfEntityReferenceFieldItemList extends EntityReferenceFieldItemList {

  /**
   * The staging candidate graphs service.
   *
   * @var \Drupal\joinup_federation\StagingCandidateGraphsInterface
   */
  protected $stagingCandidateGraphs;

  /**
   * Builds a new field item list instance.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition.
   * @param \Drupal\joinup_federation\StagingCandidateGraphsInterface $staging_candidate_graphs
   *   The staging candidate graphs service.
   * @param string|null $name
   *   (optional) The name of the created property, or NULL if it is the root
   *   of a typed data tree. Defaults to NULL.
   * @param \Drupal\Core\TypedData\TypedDataInterface $parent
   *   (optional) The parent object of the data property, or NULL if it is the
   *   root of a typed data tree. Defaults to NULL.
   */
  public function __construct(DataDefinitionInterface $definition, StagingCandidateGraphsInterface $staging_candidate_graphs, ?string $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->stagingCandidateGraphs = $staging_candidate_graphs;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL): self {
    return new static(
      $definition,
      \Drupal::service('joinup_federation.staging_candidate_graphs'),
      $name,
      $parent
    );
  }

  /**
   * {@inheritdoc}
   */
  public function referencedEntities(): array {
    $entity = !empty($this->getParent()) ? $this->getEntity() : NULL;

    if (
      $entity
      && $entity->getEntityTypeId() === 'rdf_entity'
      && $entity->get('graph')->target_id === 'staging'
      && $this->getItemDefinition()->getSetting('target_type') === 'rdf_entity'
    ) {
      return $this->getReferencedEntities();
    }

    return parent::referencedEntities();
  }

  /**
   * Returns the referenced entities.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of RDF referenced entities.
   */
  protected function getReferencedEntities(): array {
    if ($this->isEmpty()) {
      return [];
    }

    // Collect the IDs of existing entities to load, and directly grab the
    // "autocreate" entities that are already populated in $item->entity.
    $target_entities = $ids = [];
    foreach ($this->list as $delta => $item) {
      if ($item->target_id !== NULL) {
        $ids[$delta] = $item->target_id;
      }
      elseif ($item->hasNewEntity()) {
        $target_entities[$delta] = $item->entity;
      }
    }

    // Load and add the existing entities.
    if ($ids) {
      $entities = Rdf::loadMultiple($ids, $this->stagingCandidateGraphs->getCandidates());
      foreach ($ids as $delta => $target_id) {
        if (isset($entities[$target_id])) {
          $target_entities[$delta] = $entities[$target_id];
        }
      }
      // Ensure the returned array is ordered by deltas.
      ksort($target_entities);
    }

    return $target_entities;
  }

}
