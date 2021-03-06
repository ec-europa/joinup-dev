<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation_test\Plugin\pipeline\Pipeline;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\joinup_federation\JoinupFederationPipelinePluginBase;
use Drupal\pipeline\PipelineStateManager;
use Drupal\pipeline\Plugin\PipelineStepPluginManager;
use Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a pipeline testing plugin.
 *
 * @PipelinePipeline(
 *   id = "joinup_federation_pipeline_collection_uri_test",
 *   label = @Translation("Joinup federation pipeline collection URI testing"),
 *   steps = {},
 * )
 */
class JoinupFederationInvalidTestingPipeline extends JoinupFederationPipelinePluginBase {

  /**
   * The state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $stateStorage;

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
   * @param \Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface $sparql
   *   The SPARQL database connection.
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $shared_tempstore_factory
   *   The shared temp store factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state storage service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, PipelineStepPluginManager $step_plugin_manager, PipelineStateManager $state_manager, AccountProxyInterface $current_user, ConnectionInterface $sparql, SharedTempStoreFactory $shared_tempstore_factory, EntityTypeManagerInterface $entity_type_manager, StateInterface $state, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $step_plugin_manager, $state_manager, $current_user, $sparql, $shared_tempstore_factory, $entity_type_manager, $event_dispatcher);
    $this->stateStorage = $state;
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
      $container->get('sparql.endpoint'),
      $container->get('joinup_federation.tempstore.shared'),
      $container->get('entity_type.manager'),
      $container->get('state'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Allows the test to override the steps defined in annotation.
   *
   * @param array $steps
   *   Associative array keyed by step plugin ID and having the plugin
   *   configuration as values.
   */
  public function setSteps(array $steps) {
    $this->pluginDefinition['steps'] = $steps;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollection(): string {
    switch ($this->stateStorage->get('joinup_federation.test.collection')) {
      case 'missed':
        return '';

      case 'invalid':
        return 'http://invalid-collection-id';

      case 'from_annotation':
        $this->pluginDefinition['collection'] = 'http://from-annotation';
        return parent::getCollection();
    }
    throw new \Exception('Invalid test case');
  }

}
