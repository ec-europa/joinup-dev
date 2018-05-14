<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\SharedTempStore;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\pipeline\PipelineStateManager;
use Drupal\pipeline\Plugin\PipelinePipelinePluginBase;
use Drupal\pipeline\Plugin\PipelineStepPluginManager;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for Joinup ETL pipelines.
 */
abstract class JoinupFederationPipelinePluginBase extends PipelinePipelinePluginBase implements JoinupFederationPipelineInterface {

  use StringTranslationTrait;

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
   * The shared temp store service.
   *
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $sharedTempStoreFactory;

  /**
   * The shared tempstore.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $sharedTempStore;

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
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $shared_tempstore_factory
   *   The shared temp store factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PipelineStepPluginManager $step_plugin_manager, PipelineStateManager $state_manager, AccountProxyInterface $current_user, Connection $sparql, SharedTempStoreFactory $shared_tempstore_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $step_plugin_manager, $state_manager);
    $this->currentUser = $current_user;
    $this->sparql = $sparql;
    $this->sharedTempStoreFactory = $shared_tempstore_factory;
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
      $container->get('sparql_endpoint'),
      $container->get('joinup_federation.tempstore.shared')
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
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(): ?array {
    if (!$this->lock()) {
      $arguments = ['%pipeline' => $this->getPluginDefinition()['label']];
      return [
        '#markup' => $this->t("There's another ongoing import process run by other user. You cannot run %pipeline right now.'", $arguments),
      ];
    }
    // This is an extra-precaution to ensure that there's no existing data in
    // the pipeline graphs, left there after a potential failed previous run.
    $this->clearGraphs();
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function onSuccess(): JoinupFederationPipelineInterface {
    $this->clearGraphs();
    $this->lockRelease();
    return parent::onSuccess();
  }

  /**
   * {@inheritdoc}
   */
  public function onError(): JoinupFederationPipelineInterface {
    $this->clearGraphs();
    $this->lockRelease();
    return parent::onError();
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    // The reset operation can be triggered only by users granted with such
    // permissions. They are able to release even the lock owned by other user.
    $this->lockRelease(TRUE);
    parent::reset();
  }

  /**
   * {@inheritdoc}
   */
  public function clearGraphs(): JoinupFederationPipelineInterface {
    foreach ($this->getConfiguration()['graph'] as $graph_uri) {
      $this->sparql->update("CLEAR GRAPH <$graph_uri>");
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function lock(): bool {
    return $this->getSharedTempStore()->setIfOwner('pipeline.lock', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function lockRelease(bool $ignore_ownership = FALSE): void {
    if ($ignore_ownership) {
      $this->getSharedTempStore()->delete('pipeline.lock');
    }
    else {
      $this->getSharedTempStore()->deleteIfOwner('pipeline.lock');
    }
  }

  /**
   * Returns the shared temp store.
   *
   * @return \Drupal\Core\TempStore\SharedTempStore
   *   The shared temp store.
   */
  protected function getSharedTempStore(): SharedTempStore {
    if (!isset($this->sharedTempStore)) {
      $this->sharedTempStore = $this->sharedTempStoreFactory->get('joinup_federation', $this->currentUser->id());
    }
    return $this->sharedTempStore;
  }

}
