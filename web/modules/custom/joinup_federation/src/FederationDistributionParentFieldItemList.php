<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution;

use Drupal\rdf_entity\Entity\Query\Sparql\SparqlQueryInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Defines a field item list class for the distribution 'parent' field.
 */
class FederationDistributionParentFieldItemList extends DistributionParentFieldItemList {

  /**
   * {@inheritdoc}
   */
  protected function getQuery(RdfInterface $distribution): SparqlQueryInterface {
    /** @var \Drupal\joinup_federation\StagingCandidateGraphsInterface $staging_candidate_graphs */
    $staging_candidate_graphs = \Drupal::service('joinup_federation.staging_candidate_graphs');
    $graph_ids = $staging_candidate_graphs->getCandidates();
    return parent::getQuery($distribution)->graphs($graph_ids);
  }

}
