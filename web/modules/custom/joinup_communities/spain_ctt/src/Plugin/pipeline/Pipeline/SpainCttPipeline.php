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
 *     "manual_upload_step",
 *     "convert_to_adms2",
 *     "remove_unsupported_data",
 *     "add_joinup_vocabularies",
 *     "adms_validation",
 *   },
 * )
 */
class SpainCttPipeline extends JoinupFederationPipelinePluginBase {}
