<?php

namespace Drupal\pipeline\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for pipeline steps that are exposing a form.
 */
interface PipelineStepWithFormInterface extends PluginFormInterface, PluginInspectionInterface {

  /**
   * Returns data to be injected in the persistent data store.
   *
   * Relevant form values can be returned to be added to the persistent data
   * store. These values could be retrieved later, in the pipeline step plugin
   * ::execute() method:
   * @code
   * // If this method returns:
   * [
   *   'foo' => $form_state->getValue('foo'),
   *   'bar' => $form_state->getValue('bar'),
   * ];
   *
   * // In the pipeline step plugin execution method:
   * public function execute() {
   *   $foo = $this->getPersistentDataValue('foo');
   *   $bar = $this->getPersistentDataValue('bar');
   *   ...
   * }
   * @endcode
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   Data extracted from form submitted values
   */
  public function getAdditionalPersistentDataStore(FormStateInterface $form_state);

}
