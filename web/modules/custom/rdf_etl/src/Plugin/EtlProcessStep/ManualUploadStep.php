<?php

namespace Drupal\rdf_etl\Plugin\EtlProcessStep;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\rdf_etl\ProcessStepBase;

/**
 * Defines a manual data upload step.
 *
 * @EtlProcessStep(
 *  id = "manual_upload_step",
 *  label = @Translation("Manual upload"),
 * )
 */
class ManualUploadStep extends ProcessStepBase implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // TODO: Implement execute() method.
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // $data = $form_state->getBuildInfo()['data'];
    $form['adms_file'] = [
      '#type' => 'file',
      '#title' => $this->t('File'),
      '#description' => $this->t('An RDF file you want to test for compliance.'),
      '#upload_validators'  => [
        'file_validate_extensions' => ['rdf ttl'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();
    $data = &$build_info['data'];
    $data['result'] = $form_state->getValue('adms_file');
    $form_state->setBuildInfo($build_info);
  }

}
