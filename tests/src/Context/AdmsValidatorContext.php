<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions to test ADMS-AP validation.
 */
class AdmsValidatorContext extends RawDrupalContext {

  /**
   * Asserts that entities in the published graph are ADMS-AP compliant.
   *
   * @Then the ADMS-AP data of the published entities in Joinup is valid
   */
  public function assertValidPublishedGraph() {
    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface $sparql_graph */
    $sparql_graph = \Drupal::service('sparql.graph_handler');
    $graphs = array_merge(
      $sparql_graph->getEntityTypeGraphUris('rdf_entity'),
      $sparql_graph->getEntityTypeGraphUris('taxonomy_term')
    );
    /** @var \Drupal\Driver\Database\sparql\Connection $sparql_endpoint */
    $sparql_endpoint = \Drupal::service('sparql_endpoint');
    // Create a query to copy all the triples from the published graphs to
    // a validation one.
    $query = '';
    foreach ($graphs as $graph_mapping) {
      $published_graph_uri = $graph_mapping['default'];
      $query .= "ADD <$published_graph_uri> TO <http://joinup/validation-graph>; \n";
    }
    $sparql_endpoint->query($query);

    /** @var \Drupal\adms_validator\AdmsValidatorInterface $validator */
    $validator = \Drupal::service('adms_validator.validator');
    $result = $validator->validateByGraphUri('http://joinup/validation-graph');

    $sparql_endpoint->query('CLEAR GRAPH <http://joinup/validation-graph>');

    Assert::assertTrue($result->isSuccessful(), 'The published graph is not ADMS-AP compliant.');
  }

}
