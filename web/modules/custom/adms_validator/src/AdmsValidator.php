<?php

declare(strict_types = 1);

namespace Drupal\adms_validator;

use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Drupal\sparql_entity_storage\SparqlGraphStoreTrait;
use EasyRdf\Graph;

/**
 * The ADMSv2 validator service.
 */
class AdmsValidator implements AdmsValidatorInterface {

  use SparqlGraphStoreTrait;

  /**
   * The connection to the SPARQL backend.
   *
   * @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface
   */
  protected $sparqlEndpoint;

  /**
   * The validation query.
   *
   * @var string
   */
  protected $validationQuery;

  /**
   * {@inheritdoc}
   */
  public function getValidationQuery(string $graph_uri = NULL): string {
    if (empty($this->validationQuery)) {
      $this->validationQuery = self::getDefaultValidationQuery($graph_uri);
    }
    return $this->validationQuery;
  }

  /**
   * {@inheritdoc}
   */
  public function setValidationQuery(string $validation_query): AdmsValidatorInterface {
    $this->validationQuery = $validation_query;
    return $this;
  }

  /**
   * Constructs a new AdmsValidator object.
   */
  public function __construct(ConnectionInterface $sparql_endpoint) {
    $this->sparqlEndpoint = $sparql_endpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function validateByGraphUri(string $graph_uri): AdmsValidationResult {
    $query = $this->getValidationQuery($graph_uri);
    $query_result = $this->sparqlEndpoint->query($query);
    return new AdmsValidationResult($query_result, $graph_uri, $this->sparqlEndpoint);
  }

  /**
   * {@inheritdoc}
   */
  public function validateGraph(Graph $graph): AdmsValidationResult {
    if (!$uri = $graph->getUri()) {
      throw new \InvalidArgumentException("The graph has been instantiated without a URI. Should be instantiated in this way: new Graph('http://example.com/graph-uri');");
    }

    // Store the graph in the graph store.
    $result = $this->createGraphStore()->replace($graph);
    if (!$result->isSuccessful()) {
      throw new \Exception('Could not store triples in triple store.');
    }

    // Perform the validation.
    return $this->validateByGraphUri($graph->getUri());
  }

  /**
   * {@inheritdoc}
   */
  public function validateBlob(string $content, string $graph_uri): AdmsValidationResult {
    $graph = new Graph($graph_uri);
    $graph->parse($content);
    return $this->validateGraph($graph);
  }

  /**
   * {@inheritdoc}
   */
  public function validateFile(string $file_uri_or_path, string $graph_uri): AdmsValidationResult {
    return $this->validateBlob(file_get_contents($file_uri_or_path), $graph_uri);
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultValidationQuery(string $uri): string {
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
    $query = preg_replace('/(FILTER\(!EXISTS {\?o a )dct\:LinguisticSystem(}\)\.)/', '\1?some_class\2', $query);

    // Workaround for rules 13 and 32.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4568
    // @see https://github.com/SEMICeu/adms-ap_validator/issues/7
    // Remove validation rule 13.
    $query = preg_replace('/# Rule_ID:13.*?(# Rule_ID:14)/s', '\1', $query);
    // Remove validation rule 32.
    $query = preg_replace('/# Rule_ID:32.*?(# Rule_ID:33)/s', '\1', $query);

    // Workaround for a wrong definition.
    // @see https://github.com/SEMICeu/ADMS-AP/issues/2
    // @see https://github.com/SEMICeu/adms-ap_validator/issues/3
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4376
    // @todo Remove this workaround in ISAICP-4376.
    return preg_replace('/(\?s skos\:hasTopConcept \?o\.[ \n\t]+FILTER\(\!)isLiteral(\(\?o\)\)\.)/', '\1isIri\2', $query);
  }

}
