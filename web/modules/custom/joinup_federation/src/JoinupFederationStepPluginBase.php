<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation;

use Drupal\pipeline\Plugin\PipelineStepPluginBase;

/**
 * Provides a base class for Joinup ETL pipeline steps.
 */
abstract class JoinupFederationStepPluginBase extends PipelineStepPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'sink_graph' => JoinupFederationPipelinePluginBase::SINK_GRAPH,
    ] + parent::defaultConfiguration();
  }

}
