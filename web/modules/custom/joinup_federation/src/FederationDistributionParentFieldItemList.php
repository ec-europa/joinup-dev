<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation;

use Drupal\asset_distribution\DistributionParentFieldItemList;
use Drupal\rdf_entity\RdfInterface;
use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlQueryInterface;

/**
 * Defines a field item list class for the distribution 'parent' field.
 */
class FederationDistributionParentFieldItemList extends DistributionParentFieldItemList {

  /**
   * {@inheritdoc}
   */
  protected function getQuery(RdfInterface $distribution): SparqlQueryInterface {
    $query = parent::getQuery($distribution);
    if ($distribution->get('graph')->target_id === 'staging') {
      /** @var \Drupal\joinup_federation\StagingCandidateGraphsInterface $staging_candidate_graphs */
      $staging_candidate_graphs = \Drupal::service('joinup_federation.staging_candidate_graphs');
      $graph_ids = $staging_candidate_graphs->getCandidates();
      $query->graphs($graph_ids);
    }
    return $query;
  }

}
