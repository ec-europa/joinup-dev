<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Validation\Constraint;

use Drupal\asset_distribution\Plugin\Validation\Constraint\DistributionSingleParentValidator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_federation\StagingCandidateGraphsInterface;
use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlQueryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Replaces the DistributionSingleParentConstraintValidator validator.
 */
class FederationDistributionSingleParentConstraintValidator extends DistributionSingleParentValidator implements ContainerInjectionInterface {

  /**
   * The staging candidate graphs service.
   *
   * @var \Drupal\joinup_federation\StagingCandidateGraphsInterface
   */
  protected $stagingCandidateGraphs;

  /**
   * Builds a new plugin instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\joinup_federation\StagingCandidateGraphsInterface $staging_candidate_graphs
   *   The staging candidate graphs service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StagingCandidateGraphsInterface $staging_candidate_graphs) {
    parent::__construct($entity_type_manager);
    $this->stagingCandidateGraphs = $staging_candidate_graphs;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('joinup_federation.staging_candidate_graphs')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuery(): SparqlQueryInterface {
    if (!isset($this->query)) {
      $this->query = parent::getQuery();
      $this->query->graphs($this->stagingCandidateGraphs->getCandidates());
    }
    return $this->query;
  }

  /**
   * {@inheritdoc}
   */
  protected function loadMultiple(array $ids): array {
    return $this->getRdfStorage()->loadMultiple($ids, $this->stagingCandidateGraphs->getCandidates());
  }

}
