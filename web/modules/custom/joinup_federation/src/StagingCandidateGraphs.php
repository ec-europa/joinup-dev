<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation;

use Drupal\rdf_entity\RdfGraphHandlerInterface;

/**
 * Helper service to provide a list of graph candidates with 'staging' on top.
 */
class StagingCandidateGraphs implements StagingCandidateGraphsInterface {

  /**
   * The RDF entity graph handler service.
   *
   * @var \Drupal\rdf_entity\RdfGraphHandlerInterface
   */
  protected $graphHandler;

  /**
   * Static cache for the list of graph candidates.
   *
   * @var string[]
   */
  protected $candidates;

  /**
   * Builds a new service instance.
   *
   * @param \Drupal\rdf_entity\RdfGraphHandlerInterface $graph_handler
   *   The RDF entity graph handler service.
   */
  public function __construct(RdfGraphHandlerInterface $graph_handler) {
    $this->graphHandler = $graph_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getCandidates(): array {
    if (!isset($this->candidates)) {
      $this->candidates = array_merge(['staging'], $this->graphHandler->getEntityTypeDefaultGraphIds('rdf_entity'));
    }
    return $this->candidates;
  }

}
