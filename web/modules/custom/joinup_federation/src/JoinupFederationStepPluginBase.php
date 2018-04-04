<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation;

use Drupal\pipeline\Plugin\PipelineStepPluginBase;

/**
 * Provides a base class for Joinup ETL pipeline steps.
 */
abstract class JoinupFederationStepPluginBase extends PipelineStepPluginBase {

  /**
   * Returns the sink graph URI.
   *
   * @return string
   *   The sink graph URI.
   */
  protected function getSinkGraphUri() {
    return $this->getPipeline()->getSinkGraphUri();
  }

}
