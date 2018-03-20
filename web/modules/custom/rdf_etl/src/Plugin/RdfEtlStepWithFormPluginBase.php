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
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {}

}
