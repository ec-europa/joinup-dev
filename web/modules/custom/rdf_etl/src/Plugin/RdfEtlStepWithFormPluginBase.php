<?php

namespace Drupal\rdf_etl\Plugin;

use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for the for process step plugins supporting forms.
 */
abstract class RdfEtlStepWithFormPluginBase extends RdfEtlStepPluginBase implements RdfEtlStepWithFormInterface {

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $build_info = $form_state->getBuildInfo();
    $data = &$build_info['data'];
    $data += $this->extractDataFromSubmit($form_state);
    $form_state->setBuildInfo($build_info);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {}

}
