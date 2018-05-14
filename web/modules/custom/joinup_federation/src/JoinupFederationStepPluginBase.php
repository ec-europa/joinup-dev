<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\pipeline\Plugin\PipelineStepPluginBase;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for Joinup ETL pipeline steps.
 */
abstract class JoinupFederationStepPluginBase extends PipelineStepPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The SPARQL connection.
   *
   * @var \Drupal\rdf_entity\Database\Driver\sparql\Connection
   */
  protected $sparql;

  /**
   * Creates a new pipeline step plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql
   *   The SPARQL database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $sparql) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sparql = $sparql;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sparql_endpoint')
    );
  }

  /**
   * Returns the federation graph URI given a graph type.
   *
   * @param string $graph_type
   *   The type of graph.
   *
   * @return string
   *   The graph URI.
   */
  protected function getGraphUri(string $graph_type): string {
    /** @var \Drupal\joinup_federation\JoinupFederationPipelineInterface $pipeline */
    $pipeline = $this->getPipeline();
    return $pipeline->getGraphUri($graph_type);
  }

}
