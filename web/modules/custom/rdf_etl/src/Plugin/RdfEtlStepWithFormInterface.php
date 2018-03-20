<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for RDF ETL process steps that are exposing a form.
 */
interface RdfEtlStepWithFormInterface extends PluginFormInterface, PluginInspectionInterface {}
