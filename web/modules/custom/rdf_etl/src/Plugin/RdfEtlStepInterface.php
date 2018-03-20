<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Defines an interface for Process step plugins.
 */
interface RdfEtlStepInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * The graph where the triples are stored during the import process.
   *
   * @var string
   */
  const SINK_GRAPH = 'http://etl-sink/';

  /**
   * Execute the business logic of the process step (the actual ETL action).
   *
   * @param array $data
   *   An array of data to be passed to the execute method.
   *
   * @return null|array|\Drupal\Component\Render\MarkupInterface|string
   *   If no errors were encountered during the step execution, nothing should
   *   be returned. Return the error message as a render array or a markup
   *   object or as a string.
   */
  public function execute(array &$data);

}
