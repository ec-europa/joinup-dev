<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation;

use Drupal\pipeline\PipelineStateManager;
use Drupal\pipeline\Plugin\PipelinePipelinePluginBase;
use Drupal\pipeline\Plugin\PipelineStepPluginManager;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Provides a base class for Joinup ETL pipelines.
 */
abstract class JoinupFederationPipelinePluginBase extends PipelinePipelinePluginBase implements JoinupFederationPipelineInterface {

  /**
   * The current session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The SPARQL connection.
   *
   * @var \Drupal\rdf_entity\Database\Driver\sparql\Connection
   */
  protected $sparql;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\pipeline\Plugin\PipelineStepPluginManager $step_plugin_manager
   *   The step plugin manager service.
   * @param \Drupal\pipeline\PipelineStateManager $state_manager
   *   The pipeline state manager service.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The current session.
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql
   *   The SPARQL database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PipelineStepPluginManager $step_plugin_manager, PipelineStateManager $state_manager, SessionInterface $session, Connection $sparql) {
    $this->session = $session;
    $this->sparql = $sparql;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $step_plugin_manager, $state_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.pipeline_step'),
      $container->get('pipeline.state_manager'),
      $container->get('session'),
      $container->get('sparql_endpoint')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSinkGraphUri(): string {
    return $this->getConfiguration()['sink_graph'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'sink_graph' => static::SINK_GRAPH_BASE . '/' . $this->session->getId(),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function onSuccess(): void {
    parent::onSuccess();
    $this->clearGraph();
  }

  /**
   * {@inheritdoc}
   */
  public function onError(): void {
    parent::onError();
    $this->clearGraph();
  }

  /**
   * Clears the data from the sink graph.
   */
  protected function clearGraph(): void {
    $this->sparql->update("CLEAR GRAPH <{$this->getSinkGraphUri()}>");
  }

}
