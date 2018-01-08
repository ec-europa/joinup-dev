<?php

namespace Drupal\rdf_etl\Plugin\EtlProcessStep;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\rdf_etl\ProcessStepBase;

/**
 * Defines a manual data upload step.
 *
 * @EtlProcessStep(
 *  id = "pipeline_selection_step",
 *  label = @Translation("Pipeline selection"),
 * )
 */
class PipelineSelectionStep extends ProcessStepBase implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $data = $form_state->getBuildInfo()['data'];
    if (!isset($data['options'])) {
      throw new \Exception('This plugin requires the available pipeline options to be passed as a data attribute.');
    }
    $form['data_pipeline'] = [
      '#type' => 'select',
      '#title' => $this->t('Data pipeline'),
      '#options' => $data['options'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();
    $data = &$build_info['data'];
    $data['result'] = $form_state->getValue('data_pipeline');
    $form_state->setBuildInfo($build_info);
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // TODO: Implement execute() method.
  }

}
