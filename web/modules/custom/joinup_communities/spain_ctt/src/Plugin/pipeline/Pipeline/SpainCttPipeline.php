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
 *     "adms_validation",
 *     "attach_provenance_data"
 *   },
 * )
 */
class SpainCttPipeline extends JoinupFederationPipelinePluginBase {}
