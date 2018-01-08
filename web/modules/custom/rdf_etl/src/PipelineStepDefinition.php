<?php

namespace Drupal\rdf_etl;

/**
 * Class PipelineStepDefinition.
 *
 * @package Drupal\rdf_etl
 */
class PipelineStepDefinition {
  protected $pluginId;

  protected $preExecute;

  protected $postExecute;

  /**
   * PipelineStepDefinition constructor.
   *
   * @param string $plugin_id
   *   The plugin id of the process step associated with this step.
   */
  public function __construct(string $plugin_id) {
    $this->pluginId = $plugin_id;
  }

  /**
   * Set the post-execute hook.
   *
   * @param array $callback
   *   The callback to invoke.
   */
  public function setPostExecute(array $callback) : PipelineStepDefinition {
    $this->postExecute = $callback;
    return $this;
  }

  /**
   * Get the post-execute hook for this step.
   *
   * @return array
   *   The callback to invoke.
   */
  public function getPostExecute() {
    return $this->postExecute;
  }

  /**
   * Set the pre-execute hook.
   *
   * @param array $callback
   *   The callback to invoke.
   */
  public function setPreExecute(array $callback) : PipelineStepDefinition {
    $this->preExecute = $callback;
    return $this;
  }

  /**
   * Get the pre-execute hook for this step.
   *
   * @return array
   *   The callback to invoke.
   */
  public function getPreExecute() {
    return $this->preExecute;
  }

  /**
   * Return the plugin id of the process step plugin to use for this step.
   */
  public function getPluginId() : string {
    return $this->pluginId;
  }

}
