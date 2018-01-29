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
  const SEMIC_VALIDATION_QUERY_PATH = "SEMICeu/adms-ap_validator/python-rule-generator/ADMS-AP Rules .txt";

  /**
   * Validates the triples.
   *
   * @param \EasyRdf\Graph $graph
   *   The graph to verify.
   * @param string $uri
   *   (optional) The URI of the graph to use while storing and querying. If is
   *   missed, the default graph URI will be used.
   *
   * @return \Drupal\adms_validator\SchemaErrorList
   *   A list of schema validation errors.
   *
   * @throws \Exception
   *   If the triples cannot be stored in the graph store.
   */
  public function validate(Graph $graph, string $uri = self::DEFAULT_VALIDATION_GRAPH): SchemaErrorList;

}
