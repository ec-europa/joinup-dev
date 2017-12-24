<?php

namespace Drupal\rdf_etl\Plugin\EtlProcessStep;

use Drupal\Core\Form\FormStateInterface;
use Drupal\rdf_etl\PipelineSelectionBase;

/**
 * Defines a manual data upload step.
 *
 * @EtlProcessStep(
 *  id = "pipeline_selection_step",
 *  label = @Translation("Pipeline selection"),
 * )
 */
class PipelineSelectionStep extends PipelineSelectionBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = array_map(function ($pipeline) {
      return $pipeline['label'];
    }, $this->configuration['orchestrator']->getPipelines());
    $form['data_pipeline'] = [
      '#type' => 'select',
      '#title' => $this->t('Data pipeline'),
      '#options' => $options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitConfigurationForm() method.
    $a = 1;
  }

}
