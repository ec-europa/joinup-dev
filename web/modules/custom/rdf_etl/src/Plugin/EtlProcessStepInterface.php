<?php

namespace Drupal\rdf_etl\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Defines an interface for Process step plugins.
 */
interface EtlProcessStepInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Execute the business logic of the process step (the actual ETL action).
   */
  public function execute();

}
