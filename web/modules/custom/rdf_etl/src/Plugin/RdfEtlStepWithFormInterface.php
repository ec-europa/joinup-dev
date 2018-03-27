<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for RDF ETL process steps that are exposing a form.
 */
interface RdfEtlStepWithFormInterface extends PluginFormInterface, PluginInspectionInterface {

  /**
   * Extracts data from the form submitted values.
   *
   * Relevant form values can be returned to be passed in the $data parameter to
   * the step plugin ::execute() method:
   * @code
   * return [
   *   'foo' => $form_state->getValue('foo'),
   *   'bar' => $form_state->getValue('bar'),
   * ];
   * @endcode
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   Data extracted from form submitted values
   */
  public function extractDataFromSubmit(FormStateInterface $form_state): array;

}
