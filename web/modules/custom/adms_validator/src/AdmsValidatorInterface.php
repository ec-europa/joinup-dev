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
  const DEFAULT_VALIDATION_GRAPH = 'http://adms-validator/';

  /**
   * The path of the file that contains the validation rules.
   *
   * @var string
   */
  const SEMIC_VALIDATION_QUERY_PATH = 'SEMICeu/adms-ap_validator/pages/adms-ap.txt';

  /**
   * Validates the triples from a graph.
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

}
