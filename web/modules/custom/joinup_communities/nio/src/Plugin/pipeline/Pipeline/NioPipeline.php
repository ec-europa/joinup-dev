<?php

namespace Drupal\nio\Plugin\pipeline\Pipeline;

use Drupal\joinup_federation\JoinupFederationPipelinePluginBase;

/**
 * The data pipeline of NIO repository.
 *
 * @PipelinePipeline(
 *   id = "nio",
 *   label = @Translation("Slovenian Interoperability Portal - NIO"),
 *   steps = {
 *     "manual_upload",
 *     "remove_unsupported_data",
 *     "add_joinup_vocabularies",
 *     "adms_validation",
 *     "analyze_incoming_entities",
 *     "spdx_to_joinup_licence",
 *     "user_selection_filter",
 *     "3_way_merge",
 *     "broken_references",
 *     "joinup_validation",
 *     "import",
 *     "provenance_activity",
 *   },
 * )
 */
class NioPipeline extends JoinupFederationPipelinePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getCollection(): ?string {
    return 'http://nio.gov.si/nio/';
  }

}
