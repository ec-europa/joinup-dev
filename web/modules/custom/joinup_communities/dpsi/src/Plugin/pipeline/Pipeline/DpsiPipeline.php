<?php

declare(strict_types = 1);

namespace Drupal\dpsi\Plugin\pipeline\Pipeline;

use Drupal\joinup_federation\JoinupFederationPipelinePluginBase;

/**
 * The data pipeline for the Danish Interoperability collection.
 *
 * @PipelinePipeline(
 *   id = "dpsi",
 *   label = @Translation("Danish Public Sector Interoperability"),
 *   steps = {
 *     "manual_upload",
 *     "remove_unsupported_data",
 *     "add_joinup_vocabularies",
 *     "adms_validation",
 *     "analyze_incoming_entities",
 *     "user_selection_filter",
 *     "3_way_merge",
 *     "broken_references",
 *     "joinup_validation",
 *     "import",
 *     "provenance_activity",
 *   },
 * )
 */
class DpsiPipeline extends JoinupFederationPipelinePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getCollection(): string {
    return 'http://digitaliser.dk';
  }

}
