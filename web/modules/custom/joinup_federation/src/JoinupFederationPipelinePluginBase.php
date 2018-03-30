<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation;

use Drupal\pipeline\Plugin\PipelinePipelinePluginBase;

/**
 * Provides a base class for Joinup ETL pipelines.
 */
abstract class JoinupFederationPipelinePluginBase extends PipelinePipelinePluginBase {

  /**
   * The graph where the triples are stored during the import process.
   *
   * @var string
   */
  const SINK_GRAPH = 'http://etl-sink';

}
