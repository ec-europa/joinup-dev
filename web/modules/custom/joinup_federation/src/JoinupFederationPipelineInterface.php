<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation;

use Drupal\pipeline\Plugin\PipelinePipelineInterface;

/**
 * Provide an interface for Joinup federation pipeline plugins.
 */
interface JoinupFederationPipelineInterface extends PipelinePipelineInterface {

  /**
   * The base part of the URI of the graph used during the import process.
   *
   * @var string
   */
  const GRAPH_BASE = 'http://joinup-federation';

  /**
   * Returns the graph URI for a specific federation graph.
   *
   * @param string $graph_type
   *   The type of graph.
   *
   * @return string
   *   The graph URI.
   */
  public function getGraphUri(string $graph_type): string;

  /**
   * Clears the data from the federation graphs.
   *
   * @return $this
   */
  public function clearGraphs(): self;

}
