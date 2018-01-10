<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Process step plugins.
 */
abstract class EtlProcessStepBase extends PluginBase implements EtlProcessStepInterface {

}
