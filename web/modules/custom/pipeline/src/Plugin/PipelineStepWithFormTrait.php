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
    if ($data = $this->getAdditionalPersistentDataStore($form_state)) {
      $form_state->set('pipeline_data', $data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
