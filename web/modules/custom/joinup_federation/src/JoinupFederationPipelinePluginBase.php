<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\pipeline\PipelineStateManager;
use Drupal\pipeline\Plugin\PipelinePipelinePluginBase;
use Drupal\pipeline\Plugin\PipelineStepPluginManager;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for Joinup ETL pipelines.
 */
abstract class JoinupFederationPipelinePluginBase extends PipelinePipelinePluginBase implements JoinupFederationPipelineInterface {

  /**
   * The current session.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql
   *   The SPARQL database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PipelineStepPluginManager $step_plugin_manager, PipelineStateManager $state_manager, AccountProxyInterface $current_user, Connection $sparql) {
    $this->currentUser = $current_user;
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
      $container->get('current_user'),
      $container->get('sparql_endpoint')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getGraphUri(string $graph_type): string {
    return $this->getConfiguration()['graph'][$graph_type];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'graph' => [
        'sink' => static::GRAPH_BASE . '/sink/' . $this->currentUser->id(),
        'sink_plus_taxo' => static::GRAPH_BASE . '/sink-plus-taxo/' . $this->currentUser->id(),
      ]
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function onSuccess(): void {
    parent::onSuccess();
    $this->clearGraphs();
  }

  /**
   * {@inheritdoc}
   */
  public function onError(): void {
    parent::onError();
    $this->clearGraphs();
  }

  /**
   * {@inheritdoc}
   */
  public function clearGraphs(): void {
    foreach ($this->getConfiguration()['graph'] as $graph_uri) {
      $this->sparql->update("CLEAR GRAPH <$graph_uri>");
    }
  }

}
