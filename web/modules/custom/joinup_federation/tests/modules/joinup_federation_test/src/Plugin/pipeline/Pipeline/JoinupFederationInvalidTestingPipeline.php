<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation_test\Plugin\pipeline\Pipeline;

use Drupal\joinup_federation\JoinupFederationPipelinePluginBase;

/**
 * Provides a pipeline testing plugin.
 *
 * @PipelinePipeline(
 *   id = "joinup_federation_pipeline_collection_uri_test",
 *   label = @Translation("Joinup federation pipeline collection URI testing"),
 *   steps = {},
 * )
 */
class JoinupFederationInvalidTestingPipeline extends JoinupFederationPipelinePluginBase {

  /**
   * Allows the test to override the steps defined in annotation.
   *
   * @param array $steps
   *   Associative array keyed by step plugin ID and having the plugin
   *   configuration as values.
   */
  public function setSteps(array $steps) {
    $this->pluginDefinition['steps'] = $steps;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollection(): string {
    switch (\Drupal::state()->get('joinup_federation.test.collection')) {
      case 'missed':
        return '';

      case 'invalid':
        return 'http://invalid-collection-id';

      case 'from_annotation':
        $this->pluginDefinition['collection'] = 'http://from-annotation';
        return parent::getCollection();
    }
    throw new \Exception('Invalid test case');
  }

}
