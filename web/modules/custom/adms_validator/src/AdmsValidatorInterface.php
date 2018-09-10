<?php

namespace Drupal\adms_validator;

use EasyRdf\Graph;

/**
 * Provides an interface for the ADMSv2 validator service.
 */
interface AdmsValidatorInterface {

  /**
   * The URI of the graph used for validation.
   *
   * @var string
   */
  const DEFAULT_VALIDATION_GRAPH = 'http://adms-validator';

  /**
   * The path of the file that contains the validation rules.
   *
   * @var string
   */
  const SEMIC_VALIDATION_QUERY_PATH = 'SEMICeu/adms-ap_validator/pages/adms-ap.txt';

  /**
   * Validates the triples stored in a graph, given the graph URI.
   *
   * @param string $graph_uri
   *   The graph URI.
   *
   * @return \Drupal\adms_validator\AdmsValidationResult
   *   A list of schema validation errors.
   */
  public function validateByGraphUri(string $graph_uri): AdmsValidationResult;

  /**
   * Validates the triples from a graph object.
   *
   * @param \EasyRdf\Graph $graph
   *   The graph to verify.
   *
   * @return \Drupal\adms_validator\AdmsValidationResult
   *   A list of schema validation errors.
   */
  public function validateGraph(Graph $graph): AdmsValidationResult;

  /**
   * Validates the triples from a blob of RDF content.
   *
   * @param string $content
   *   The RDF content to be validated.
   * @param string $graph_uri
   *   The graph URI.
   *
   * @return \Drupal\adms_validator\AdmsValidationResult
   *   A list of schema validation errors.
   */
  public function validateBlob(string $content, string $graph_uri): AdmsValidationResult;

  /**
   * Validates the triples from a file.
   *
   * @param string $file_uri_or_path
   *   The RDF file URI or path.
   * @param string $graph_uri
   *   The graph URI.
   *
   * @return \Drupal\adms_validator\AdmsValidationResult
   *   A list of schema validation errors.
   */
  public function validateFile(string $file_uri_or_path, string $graph_uri): AdmsValidationResult;

  /**
   * Returns the validation query that is set to be used by the validator.
   *
   * @param string $graph_uri
   *   The graph uri to replace the in the query.
   *
   * @return string
   *   The validation query as a string.
   */
  public function getValidationQuery(string $graph_uri): string;

  /**
   * Sets the validation query to be used by the adms validator.
   *
   * @param string $validation_query
   *   The validation query as a string.
   */
  public function setValidationQuery(string $validation_query): void;

  /**
   * Builds the default SPARQL query to be used for validation.
   *
   * @param string $uri
   *   The graph URI.
   *
   * @return string
   *   The query to use for validation.
   */
   public static function getDefaultValidationQuery(string $uri): string;

}
