<?php

namespace Drupal\rdf_etl;

class PipelineStepDefinition {
  protected $plugin_id;

  protected $preExecute;

  protected $postExecute;

  protected $order;

  function __construct($plugin_id, $order) {
    $this->plugin_id = $plugin_id;
    $this->order = $order;
  }

  /**
   * @param array $callback
   *
   * $return $this
   */
  public function setPostExecute(array $callback) {
    $this->postExecute = $callback;
    return $this;
  }

  /**
   * @return array
   */
  public function getPostExecute() {
    return $this->postExecute;
  }

  /**
   * @param array $callback
   *
   * @return $this
   */
  public function setPreExecute(array $callback) {
    $this->preExecute = $callback;
    return $this;
  }

  /**
   * @return array
   */
  public function getPreExecute() {
    return $this->preExecute;
  }

  public function getPluginId() {
    return $this->plugin_id;
  }

  public function getOrder() {
    return $this->order;
  }

}
