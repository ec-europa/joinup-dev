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
 *     "convert_to_adms2",
 *     "remove_unsupported_data",
 *     "add_joinup_vocabularies",
 *     "adms_validation",
 *     "user_selection_filter",
 *     "empty_fields_values" = {
 *       "collection" = "http://administracionelectronica.gob.es/ctt",
 *     },
 *     "broken_references",
 *     "drupal_validation",
 *     "3_way_merge",
 *     "provenance_activity",
 *   },
 * )
 */
class SpainCttPipeline extends JoinupFederationPipelinePluginBase {}
