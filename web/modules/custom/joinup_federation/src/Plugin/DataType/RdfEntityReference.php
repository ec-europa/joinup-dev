<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\DataType;

use Drupal\Core\Entity\Plugin\DataType\EntityReference;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\joinup_federation\StagingCandidateGraphsInterface;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Replacement class for the core 'entity_reference' data type.
 *
 * @see \Drupal\Core\Entity\Plugin\DataType\EntityReference
 */
class RdfEntityReference extends EntityReference {

  /**
   * The staging candidate graphs service.
   *
   * @var \Drupal\joinup_federation\StagingCandidateGraphsInterface
   */
  protected $stagingCandidateGraphs;

  /**
   * Constructs a new instance.
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
  public function getTarget() {
    /** @var \Drupal\Core\Entity\Plugin\DataType\EntityAdapter $root */
    $root = $this->getRoot();

    // When the host entity is an RDF entity in 'staging' graph, use a custom
    // target loader that gives priority to the 'staging‘ graph when loads the
    // target entity.
    if (
      // Has a non-empty root.
      !$root->isEmpty()
      // And the root object is an entity adapter.
      && $root->getPluginDefinition()['id'] === 'entity'
      // And points to a non-empty entity.
      && ($host_entity = $root->getValue())
      // And the entity type is 'rdf_entity'.
      && $host_entity->getEntityTypeId() === 'rdf_entity'
      // And the RDF entity is in 'staging' graph.
      && $host_entity->get('graph')->target_id === 'staging'
      // And the target entity is an 'rdf_entity'.
      && $this->getTargetDefinition()->getEntityTypeId() === 'rdf_entity'
    ) {
      return $this->getStagingTarget();
    }

    return parent::getTarget();
  }

  /**
   * Gets the target for RDF host entities from 'staging' graph.
   *
   * The method is a slightly changed copy of parent::getTarget().
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface|null
   *   The referenced typed data object, or NULL if the reference is unset.
   *
   * @see \Drupal\Core\Entity\Plugin\DataType\EntityReference::getTarget()
   */
  protected function getStagingTarget(): ?TypedDataInterface {
    if (!isset($this->target) && isset($this->id)) {
      $entity = Rdf::load($this->id, $this->stagingCandidateGraphs->getCandidates());
      $this->target = isset($entity) ? $entity->getTypedData() : NULL;
    }
    return $this->target;
  }

}
