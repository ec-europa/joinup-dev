<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\SharedTempStore;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\pipeline\PipelineStateManager;
use Drupal\pipeline\Plugin\PipelinePipelinePluginBase;
use Drupal\pipeline\Plugin\PipelineStepPluginManager;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for Joinup ETL pipelines.
 */
abstract class JoinupFederationPipelinePluginBase extends PipelinePipelinePluginBase implements JoinupFederationPipelineInterface {

  use StringTranslationTrait;

  /**
   * The SPARQL connection.
   *
   * @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface
   */
  protected $sparql;

  /**
   * The shared temp store service.
   *
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $sharedTempStoreFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The shared tempstore.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $sharedTempStore;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql
   *   The SPARQL database connection.
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $shared_tempstore_factory
   *   The shared temp store factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PipelineStepPluginManager $step_plugin_manager, PipelineStateManager $state_manager, AccountProxyInterface $current_user, ConnectionInterface $sparql, SharedTempStoreFactory $shared_tempstore_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;

    parent::__construct($configuration, $plugin_id, $plugin_definition, $step_plugin_manager, $state_manager);

    $this->sparql = $sparql;
    $this->sharedTempStoreFactory = $shared_tempstore_factory;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('joinup_federation.tempstore.shared'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCollection(): ?string {
    return NULL;
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
        'sink' => 'http://joinup-federation/sink',
        'sink_plus_taxo' => 'http://joinup-federation/sink-plus-taxo',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function prepare() {
    if (!$this->lock()) {
      $arguments = ['@pipeline' => $this->getPluginDefinition()['label']];
      return $this->t("There's another ongoing import process run by other user. You cannot run '@pipeline' right now.", $arguments);
    }
    // This is an extra-precaution to ensure that there's no existing data in
    // the pipeline graphs, left there after a potential failed previous run.
    $this->clearStagingEntitiesCache();
    $this->clearGraphs();
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function onSuccess(): JoinupFederationPipelineInterface {
    $this->clearStagingEntitiesCache();
    $this->clearGraphs();
    $this->lockRelease();
    parent::onSuccess();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuccessMessage() {
    $build = $rows = [];
    $state = $this->getCurrentState();
    $entity_ids = array_keys($state->getDataValue('entities'));
    $non_critical_violations = $state->hasDataValue('non_critical_violations') ? $state->getDataValue('non_critical_violations') : [];

    $build[] = [
      '#markup' => $this->t('Imported entities:'),
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
    ];

    if ($non_critical_violations) {
      $build[] = [
        '#markup' => $this->t("Some of the imported entities are still missing information. You can find bellow the fields that should have values. Use the user interface to edit each entity and fill the missed field values."),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
    }

    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    foreach (Rdf::loadMultiple($entity_ids) as $id => $entity) {
      $rows[] = [
        [
          'colspan' => $non_critical_violations ? 2 : 1,
          'data' => [
            [
              '#markup' => $this->t("@type: %name", [
                '@type' => $entity->get('rid')->entity->label(),
                '%name' => $entity->label() ? ($entity->label() . ' [' . $entity->id() . ']') : $entity->id(),
              ]),
              '#prefix' => '<strong>',
              '#suffix' => '</strong>',
            ],
          ],
        ],
      ];

      if (isset($non_critical_violations[$id])) {
        foreach ($non_critical_violations[$id] as $message) {
          $rows[] = [
            [
              'data' => $message['field'] ?? $this->t('N/A'),
            ],
            $message['message'],
          ];
        }
      }
    }

    $header = $non_critical_violations ? [$this->t('Field'), $this->t('Warning')] : [$this->t('Entities')];
    $build[] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function onError(): JoinupFederationPipelineInterface {
    $this->clearStagingEntitiesCache();
    $this->clearGraphs();
    $this->lockRelease();
    return parent::onError();
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    // The reset operation can be triggered only by users granted with such
    // permissions. They are able to release even the lock owned by other users.
    $this->lockRelease(TRUE);
    parent::reset();
  }

  /**
   * {@inheritdoc}
   */
  public function clearGraph(string $graph_uri): JoinupFederationPipelineInterface {
    $this->sparql->update("CLEAR GRAPH <$graph_uri>");
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function clearGraphs(): JoinupFederationPipelineInterface {
    foreach ($this->getConfiguration()['graph'] as $graph_uri) {
      $this->clearGraph($graph_uri);
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

  /**
   * Clears entity cache from the 'staging' graph.
   */
  protected function clearStagingEntitiesCache(): void {
    // Get all entity IDs from the graph.
    $result = $this->sparql->query("SELECT DISTINCT(?id) WHERE { GRAPH <{$this->getGraphUri('sink')}> { ?id ?p ?o . } }");
    $ids = [];
    foreach ($result as $item) {
      $ids[] = $item->id->getUri();
    }

    if ($ids) {
      /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('rdf_entity');
      $storage->resetCache($ids, ['staging']);
    }
  }

}
