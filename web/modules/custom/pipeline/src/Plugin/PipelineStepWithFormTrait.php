<?php

namespace Drupal\pipeline\Plugin;

use Drupal\Core\Form\FormStateInterface;

/**
 * Reusable code for pipeline step plugins with form.
 */
trait PipelineStepWithFormTrait {

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();
    $data = &$build_info['data'];
    $data += $this->extractDataFromSubmit($form_state);
    $form_state->setBuildInfo($build_info);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
