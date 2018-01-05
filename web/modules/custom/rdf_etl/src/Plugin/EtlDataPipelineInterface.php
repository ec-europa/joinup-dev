<?php

namespace Drupal\rdf_etl\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Data pipeline plugins.
 */
interface EtlDataPipelineInterface extends PluginInspectionInterface {

  function getStepDefinitions();
  // Add get/set methods for your plugin type here.

}
