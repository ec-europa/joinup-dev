<?php

namespace Drupal\rdf_etl\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Defines an interface for Process step plugins.
 */
interface EtlProcessStepInterface extends PluginInspectionInterface, ConfigurablePluginInterface {
  // Add get/set methods for your plugin type here.
}
