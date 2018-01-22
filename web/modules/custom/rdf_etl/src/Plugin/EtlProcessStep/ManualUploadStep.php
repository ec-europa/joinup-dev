<?php

declare(strict_types = 1);

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
  public function execute(array $data): void {
    // TODO: Implement execute() method.
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['adms_file'] = [
      '#type' => 'file',
      '#title' => $this->t('File'),
      '#description' => $this->t('Please upload a file to use for federation. Allowed types: @extensions.', [
        '@extensions' => 'rdf ttl',
      ]),
      '#upload_validators'  => [
        'file_validate_extensions' => ['rdf ttl'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::validateConfigurationForm($form, $form_state);
    $form_state->setError($form['adms_file'], $this->t('Invalid file'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $build_info = $form_state->getBuildInfo();
    $data = &$build_info['data'];
    $data['result'] = $form_state->getValue('adms_file');
    $form_state->setBuildInfo($build_info);
  }

}
