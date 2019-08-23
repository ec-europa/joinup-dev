<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Plugin\PipelineStepWithClientRedirectResponseTrait;
use Drupal\pipeline\Plugin\PipelineStepWithResponseInterface;
use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlArg;
use Drupal\sparql_entity_storage\Entity\SparqlMapping;
use Drupal\sparql_entity_storage\SparqlGraphStoreTrait;

/**
 * Defines a process step that removes the triples not supported by Joinup.
 *
 * Scan the imported triples (which are now in the sink graph) and filter out
 * all that are not Joinup entities, as solutions, releases, distributions,
 * owners or contact information.
 *
 * @PipelineStep(
 *   id = "remove_unsupported_data",
 *   label = @Translation("Remove data not supported by Joinup"),
 * )
 */
class RemoveUnsupportedData extends JoinupFederationStepPluginBase implements PipelineStepWithResponseInterface {

  use PipelineStepWithClientRedirectResponseTrait;
  use SparqlGraphStoreTrait;

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $graph_uri = $this->getGraphUri('sink');

    $rdf_entity_bundle_uris = [];
    /** @var \Drupal\sparql_entity_storage\SparqlMappingInterface $mapping */
    foreach (SparqlMapping::loadMultiple() as $mapping) {
      // Only add rdf:type URI for RDF entities. We exclude the 'collection' RDF
      // entity bundle because:
      // - Usually a pipeline is already an effect of an existing Joinup
      //   collection. A pipeline will most likely be a 1-1 mapped to an
      //   existing collection.
      // - Collections are exposing also a lot of Joinup/Drupal specific logic
      //   (such as OG, etc.) and that cannot be provided via an import.
      // We also exclude licenses are these are not supposed to be imported by
      // any external repository. Licenses that do not exist as an option in
      // the site, should be proposed through the support form before importing
      // related entities.
      // @todo Reconsider this decision, if case.
      if ($mapping->getTargetEntityTypeId() === 'rdf_entity' && !in_array($mapping->getTargetBundle(), ['collection', 'licence'])) {
        $rdf_entity_bundle_uris[] = $mapping->getRdfType();
      }
    }
    $rdf_entity_bundle_uris = SparqlArg::serializeUris($rdf_entity_bundle_uris);

    $query = <<<Ouery
DELETE FROM <$graph_uri> {
  ?subject ?predicate ?object
}
WHERE {
  ?subject ?predicate ?object
  {
    SELECT DISTINCT(?subject)
    FROM NAMED <$graph_uri>
    WHERE {
      GRAPH <$graph_uri> {
        ?subject <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ?object .
        FILTER ( ?object NOT IN ( $rdf_entity_bundle_uris ) ) .
      }
    }
  }
}
Ouery;
    $this->sparql->query($query);
  }

}
