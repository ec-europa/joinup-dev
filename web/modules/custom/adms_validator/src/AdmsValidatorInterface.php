<?php

namespace Drupal\adms_validator;

use EasyRdf\Graph;

/**
 * Interface AdmsValidatorInterface.
 */
interface AdmsValidatorInterface {

  /**
   * Store triples in temporary graph.
   *
   * @param \EasyRdf\Graph $graph
   *   The graph to verify.
   *
   * @throws \Exception
   */
  public function storeGraph(Graph $graph) : void;

  /**
   * Validate the result.
   *
   * @return \Drupal\adms_validator\SchemaErrorList
   *   A list of schema validation errors.
   */
  public function validateGraph() : SchemaErrorList;

  /**
   * Sets the URI of the graph to use while validating.
   *
   * @param string $uri
   *   The URI of the graph to use while storing and querying.
   */
  public function setGraphUri(string $uri) : void;

}
