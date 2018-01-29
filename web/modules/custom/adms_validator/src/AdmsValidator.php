<?php

declare(strict_types = 1);

namespace Drupal\adms_validator;

use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use EasyRdf\Graph;
use EasyRdf\GraphStore;

/**
 * The ADMSv2 validator service.
 */
class AdmsValidator implements AdmsValidatorInterface {

  /**
   * The connection to the SPARQL backend.
   *
   * @var \Drupal\rdf_entity\Database\Driver\sparql\Connection
   */
  protected $sparqlEndpoint;

  /**
   * The graph store connect string.
   *
   * @var string
   */
  protected $connectString;

  /**
   * Constructs a new AdmsValidator object.
   */
  public function __construct(Connection $sparql_endpoint) {
    $this->sparqlEndpoint = $sparql_endpoint;
    $connection_options = $this->sparqlEndpoint->getConnectionOptions();
    $this->connectString = 'http://' . $connection_options['host'] . ':' . $connection_options['port'] . '/sparql-graph-crud';
  }

  /**
   * {@inheritdoc}
   */
  public function validate(Graph $graph, string $uri = self::DEFAULT_VALIDATION_GRAPH): SchemaErrorList {
    // Use a local SPARQL 1.1 Graph Store.
    $graph_store = new GraphStore($this->connectString);

    // Store the graph in the graph store.
    $output = $graph_store->replace($graph, $uri);
    if (!$output->isSuccessful()) {
      throw new \Exception('Could not store triples in triple store.');
    }

    // Perform the validation.
    $query_result = $this->sparqlEndpoint->query(self::validationQuery($uri));

    return new SchemaErrorList($query_result);
  }

  /**
   * Builds the SPARQL query to be used for validation.
   *
   * @param string $uri
   *   The graph URI.
   *
   * @return string
   *   The query to use for validation.
   */
  protected function validationQuery(string $uri): string {
    $adms_ap_rules = DRUPAL_ROOT . "/../vendor/" . self::SEMIC_VALIDATION_QUERY_PATH;
    $query = file_get_contents($adms_ap_rules);
    // Fill in our validation graph in the query.
    $query = str_replace('GRAPH <@@@TOKEN-GRAPH@@@> {

UNION', "GRAPH <$uri> { ", $query);
    // @todo Workaround for bug in validations query.
    // @see https://github.com/SEMICeu/adms-ap_validator/issues/1
    return str_replace('FILTER(!EXISTS {?o a }).', 'FILTER(!EXISTS {?o a spdx:checksumValue}).', $query);
  }

}
