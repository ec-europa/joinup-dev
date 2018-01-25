<?php

declare(strict_types = 1);

namespace Drupal\adms_validator;

use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use EasyRdf\Graph;
use EasyRdf\GraphStore;

/**
 * Class AdmsValidator.
 */
class AdmsValidator implements AdmsValidatorInterface {
  /**
   * The name of the graph used for validation.
   *
   * @var string
   */
  const DEFAULT_VALIDATION_GRAPH = 'http://adms-validator/';

  /**
   * The path of the file that contains the validation rules.
   *
   * @var string
   */
  const SEMIC_VALIDATION_QUERY_PATH = "SEMICeu/adms-ap_validator/python-rule-generator/ADMS-AP Rules .txt";

  protected $validationGraphURI = self::DEFAULT_VALIDATION_GRAPH;


  /**
   * Drupal\rdf_entity\Database\Driver\sparql\Connection definition.
   *
   * @var \Drupal\rdf_entity\Database\Driver\sparql\Connection
   */
  protected $sparqlEndpoint;

  protected $graphStore;

  /**
   * Constructs a new AdmsValidator object.
   */
  public function __construct(Connection $sparql_endpoint) {
    $this->sparqlEndpoint = $sparql_endpoint;

    $connection_options = $this->sparqlEndpoint->getConnectionOptions();
    $connect_string = 'http://' . $connection_options['host'] . ':' . $connection_options['port'] . '/sparql-graph-crud';
    // Use a local SPARQL 1.1 Graph Store.
    $this->graphStore = new GraphStore($connect_string);
  }

  /**
   * {@inheritdoc}
   */
  public function storeGraph(Graph $graph) : void {
    $out = $this->graphStore->replace($graph, $this->validationGraphURI);
    if (!$out->isSuccessful()) {
      throw new \Exception('Could not store triples in triple store.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateGraph() : SchemaErrorList {
    $query_result = $this->sparqlEndpoint->query(self::validationQuery());
    return new SchemaErrorList($query_result);
  }

  /**
   * {@inheritdoc}
   */
  public function setGraphUri(string $uri) : void {
    $this->validationGraphURI = $uri;
  }

  /**
   * Build the SPARQL query to use for validation.
   *
   * @return string
   *   The query to use for validation.
   */
  protected function validationQuery() : string {
    $adms_ap_rules = DRUPAL_ROOT . "/../vendor/" . self::SEMIC_VALIDATION_QUERY_PATH;
    $query = file_get_contents($adms_ap_rules);
    // Fill in our validation graph in the query.
    $query = str_replace('GRAPH <@@@TOKEN-GRAPH@@@> {

UNION', "GRAPH <" . $this->validationGraphURI . "> { ", $query);
    // @todo Workaround for bug in validations query.
    // See https://github.com/SEMICeu/adms-ap_validator/issues/1
    return str_replace('FILTER(!EXISTS {?o a }).', 'FILTER(!EXISTS {?o a spdx:checksumValue}).', $query);
  }

}
