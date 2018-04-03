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
  const SINK_GRAPH_BASE = 'http://etl-sink';

  /**
   * Returns the sink graph URI.
   *
   * @return string
   *   The sink graph URI.
   */
  public function getSinkGraphUri(): string;

}
