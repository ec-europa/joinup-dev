<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\adms_validator\AdmsValidatorInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Exception\PipelineStepExecutionLogicException;
use Drupal\pipeline\Plugin\PipelineStepWithBatchInterface;
use Drupal\pipeline\Plugin\PipelineStepWithBatchTrait;
use Drupal\pipeline\Plugin\PipelineStepWithRedirectResponseTrait;
use Drupal\pipeline\Plugin\PipelineStepWithResponseInterface;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a pipeline step that validates uploaded data.
 *
 * @PipelineStep(
 *   id = "adms_validation",
 *   label = @Translation("ADMS Validation"),
 * )
 */
class AdmsValidation extends JoinupFederationStepPluginBase implements PipelineStepWithResponseInterface, PipelineStepWithBatchInterface {

  use PipelineStepWithRedirectResponseTrait;
  use PipelineStepWithBatchTrait;

  /**
   * The batch size.
   *
   * @var int
   */
  const BATCH_SIZE = 1;

  /**
   * The ADMS validator service.
   *
   * @var \Drupal\adms_validator\AdmsValidatorInterface
   */
  protected $admsValidator;

  /**
   * Constructs a new 'adms_validation' pipeline step plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql
   *   The SPARQL database connection.
   * @param \Drupal\adms_validator\AdmsValidatorInterface $adms_validator
   *   The ADMS validator service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConnectionInterface $sparql, AdmsValidatorInterface $adms_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->admsValidator = $adms_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sparql_endpoint'),
      $container->get('adms_validator.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initBatchProcess() {
    $graph_uri = $this->getGraphUri('sink_plus_taxo');
    $query = $this->admsValidator->getValidationQuery($graph_uri);
    preg_match('/GRAPH.*?\{(?<where_clause>.*)\}.*?}.*?\Z/s', $query, $matches);
    $sub_queries = explode('UNION', $matches['where_clause']);
    $this->setBatchValue('queries', $sub_queries);
    return ceil(count($sub_queries, self::BATCH_SIZE));
  }

  /**
   * {@inheritdoc}
   */
  public function batchProcessIsCompleted() {
    return !$this->getBatchValue('queries');
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $query_array = $this->extractNextSubset('queries', self::BATCH_SIZE);
    foreach ($query_array as $query) {
      $query = $this->getPreparedQuery($query);
      $graph_uri = $this->getGraphUri('sink_plus_taxo');

      $validation = $this->admsValidator
        ->setValidationQuery($query)
        ->validateByGraphUri($graph_uri);

      if (!$validation->isSuccessful()) {
        throw (new PipelineStepExecutionLogicException())->setError($validation->toRows());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildBatchProcessErrorMessage() {
    $rows = array_reduce($this->getBatchErrorMessages(), function (array $rows, array $row_group): array {
      return array_merge($rows, $row_group);
    }, []);

    if (!$rows) {
      return $rows;
    }

    return [
      [
        [
          '#markup' => $this->t('Imported data is not ADMS v2 compliant:'),
        ],
        [
          '#theme' => 'table',
          '#header' => [
            $this->t('Class name'),
            $this->t('Message'),
            $this->t('Object'),
            $this->t('Predicate'),
            $this->t('Rule description'),
            $this->t('Rule ID'),
            $this->t('Rule severity'),
            $this->t('Subject'),
          ],
          '#rows' => $rows,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function onBatchProcessCompleted() {
    // Cleanup the 'sink_plus_taxo' graph.
    $this->pipeline->clearGraph($this->getGraphUri('sink_plus_taxo'));
    return $this;
  }

  /**
   * Prepares and returns a query ready to be executed.
   *
   * @param string $where_clause
   *   The where clause part of the query.
   *
   * @return string
   *   The query prepared for execution.
   */
  protected function getPreparedQuery($where_clause) {
    $graph = $this->getGraphUri('sink_plus_taxo');
    $query = <<<QUERY
PREFIX dct: <http://purl.org/dc/terms/>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
PREFIX vcard: <http://www.w3.org/2006/vcard/ns>
PREFIX v: <http://www.w3.org/2006/vcard/ns#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX cpsvapit: <http://dati.gov.it/onto/cpsvapit#>
PREFIX dcatapit: <http://dati.gov.it/onto/dcatapit#>
PREFIX locn: <https://www.w3.org/ns/locn#>
PREFIX dataeu: <http://data.europa.eu/m8g/>
PREFIX cpsv: <http://purl.org/vocab/cpsv#>
PREFIX dcat: <http://www.w3.org/ns/dcat#>
PREFIX adms: <http://www.w3.org/ns/adms#>
PREFIX spdx: <http://spdx.org/rdf/terms#>
PREFIX schema: <http://schema.org/>

SELECT ?Class_Name ?Rule_ID ?Rule_Severity ?Rule_Description ?Message (?s AS ?Subject) (?p AS ?Predicate)
WHERE{
GRAPH <{$graph}>
{$where_clause}
}
QUERY;

    return $query;
  }

}
