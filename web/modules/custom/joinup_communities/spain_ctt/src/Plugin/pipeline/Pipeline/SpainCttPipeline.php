<?php

namespace Drupal\spain_ctt\Plugin\pipeline\Pipeline;

use Drupal\joinup_federation\JoinupFederationPipelinePluginBase;

/**
 * The data pipeline of Spanish repository.
 *
 * @PipelinePipeline(
 *   id = "spain",
 *   label = @Translation("Spain - Center for Technology Transfer"),
 *   steps = {
 *     "manual_upload",
 *     "remove_unsupported_data",
 *     "add_joinup_vocabularies",
 *     "adms_validation",
 *     "user_selection_filter",
 *     "3_way_merge" = {
 *       "collection" = "http://administracionelectronica.gob.es/ctt",
 *     },
 *     "broken_references",
 *     "joinup_validation",
 *     "import",
 *     "provenance_activity" = {
 *       "collection" = "http://administracionelectronica.gob.es/ctt",
 *     },
 *   },
 * )
 */
class SpainCttPipeline extends JoinupFederationPipelinePluginBase {}
