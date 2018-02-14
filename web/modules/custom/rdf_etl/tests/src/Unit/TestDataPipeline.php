<?php

namespace Drupal\Tests\rdf_etl\Unit;

use Drupal\rdf_etl\Plugin\EtlDataPipelineBase;

/**
 * Class TestDataPipeline.
 *
 * @package Drupal\Tests\rdf_etl
 */
class TestDataPipeline extends EtlDataPipelineBase {

  /**
   * {@inheritdoc}
   */
  protected function initStepDefinition(): void {
    $this->steps->add('test_step')
      ->registerHook('pre_form_execution', [$this, 'testPreFormExecution'])
      ->registerHook('post_form_execution', [$this, 'testPostFormExecution']);
  }

  /**
   * Pre form execution callback.
   */
  public function testPreFormExecution($data) {
    return $data;
  }

  /**
   * Post form execution callback.
   */
  public function testPostFormExecution($data) {
    return $data;
  }

}
