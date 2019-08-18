<?php

namespace Drupal\joinup_collection\Plugin\pipeline\Pipeline;

use Drupal\joinup_federation\JoinupFederationPipelinePluginBase;

/**
 * The data pipeline for the Joinup collection.
 *
 * @PipelinePipeline(
 *   id = "joinup_collection",
 *   label = @Translation("Joinup collection"),
 *   steps = {
 *     "manual_upload",
 *     "remove_unsupported_data",
 *     "add_joinup_vocabularies",
 *     "adms_validation",
 *     "analyze_incoming_solutions",
 *     "user_selection_filter",
 *     "3_way_merge",
 *     "broken_references",
 *     "joinup_validation",
 *     "import",
 *     "provenance_activity",
 *   },
 * )
 */
class JoinupCollectionPipeline extends JoinupFederationPipelinePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getCollection(): ?string {
    return 'http://placeHolder/11c81d8f-1527-4044-a694-b847d66362e1';
  }

}
