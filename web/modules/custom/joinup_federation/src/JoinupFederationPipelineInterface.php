<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation;

use Drupal\pipeline\Plugin\PipelinePipelineInterface;

/**
 * Provide an interface for Joinup federation pipeline plugins.
 */
interface JoinupFederationPipelineInterface extends PipelinePipelineInterface {

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
   * Clears the data from a given graph.
   *
   * @param string $graph_uri
   *   The URI of the graph to be emptied.
   */
  public function clearGraph(string $graph_uri): void;

  /**
   * Clears the data from the federation graphs.
   */
  public function clearGraphs(): void;

}
