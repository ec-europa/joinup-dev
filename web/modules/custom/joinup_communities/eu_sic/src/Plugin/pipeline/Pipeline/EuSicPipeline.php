<?php

namespace Drupal\eu_sic\Plugin\pipeline\Pipeline;

use Drupal\joinup_federation\JoinupFederationPipelinePluginBase;

/**
 * The data pipeline for the EU SIC collection.
 *
 * @PipelinePipeline(
 *   id = "eu_sic",
 *   label = @Translation("EU Schemantic Interoperability Catalogue"),
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
class EuSicPipeline extends JoinupFederationPipelinePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getCollection(): ?string {
    return 'http://data.europa.eu/w21/b1e19fbc-f96e-478a-a449-fdaaeed17e3a';
  }

}
