<?php

declare(strict_types = 1);

namespace Drupal\adms_validator;

use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_entity\RdfEntityGraphStoreTrait;
use EasyRdf\Graph;

/**
 * The ADMSv2 validator service.
 */
class AdmsValidator implements AdmsValidatorInterface {

  use RdfEntityGraphStoreTrait;

  /**
   * The connection to the SPARQL backend.
   *
   * @var \Drupal\rdf_entity\Database\Driver\sparql\Connection
   */
  protected $sparqlEndpoint;

  /**
   * Constructs a new AdmsValidator object.
   */
  public function __construct(Connection $sparql_endpoint) {
    $this->sparqlEndpoint = $sparql_endpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function validateGraph(string $graph_uri): AdmsValidationResult {
    $query_result = $this->sparqlEndpoint->query(self::validationQuery($graph_uri));

    return new AdmsValidationResult($query_result, $graph_uri, $this->sparqlEndpoint);
  }

  /**
   * {@inheritdoc}
   */
  public function validateGraphObject(Graph $graph): AdmsValidationResult {
    if (!$uri = $graph->getUri()) {
      throw new \InvalidArgumentException("The graph has been instantiated without a URI. Should be instantiated in this way: new Graph('http://example.com/graph-uri');");
    }

    // Store the graph in the graph store.
    $result = $this->createGraphStore()->replace($graph);
    if (!$result->isSuccessful()) {
      throw new \Exception('Could not store triples in triple store.');
    }

    // Perform the validation.
    return $this->validateGraph($graph->getUri());
  }

  /**
   * {@inheritdoc}
   */
  public function validateBlob(string $content, string $graph_uri): AdmsValidationResult {
    $graph = new Graph($graph_uri);
    $graph->parse($content);
    return $this->validateGraphObject($graph);
  }

  /**
   * {@inheritdoc}
   */
  public function validateFile(string $file_uri_or_path, string $graph_uri): AdmsValidationResult {
    return $this->validateBlob(file_get_contents($file_uri_or_path), $graph_uri);
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
    $query = str_replace('@@@TOKEN-GRAPH@@@', $uri, $query);

    // Workaround for the disputed rule 41.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4350
    // @see https://github.com/SEMICeu/adms-ap_validator/issues/5
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4379
    $query = preg_replace('/(FILTER\(!EXISTS {\?o a )dct\:MediaTypeOrExtent(}\)\.)/', '\1?some_class\2', $query);

    // @todo Workaround for bug in validations query.
    // @see https://github.com/SEMICeu/adms-ap_validator/issues/1
    $query = str_replace('FILTER(!EXISTS {?o a }).', 'FILTER(!EXISTS {?o a spdx:checksumValue}).', $query);

    // Workaround for the disputed rule 15.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4348
    // @see https://github.com/SEMICeu/ADMS-AP/issues/5
    // @see https://github.com/SEMICeu/adms-ap_validator/issues/4
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4378
    $query = preg_replace('/(FILTER\(!EXISTS {\?o a )dct\:LinguisticSystem(}\)\.)/', '\1?some_class\2', $query);

    // Workaround for a wrong definition.
    // @see https://github.com/SEMICeu/ADMS-AP/issues/2
    // @see https://github.com/SEMICeu/adms-ap_validator/issues/3
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4376
    // @todo Remove this workaround in ISAICP-4376.
    return preg_replace('/(\?s skos\:hasTopConcept \?o\.[ \n\t]+FILTER\(\!)isLiteral(\(\?o\)\)\.)/', '\1isIri\2', $query);
  }

}
