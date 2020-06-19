<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\pipeline\Exception\PipelineStepPrepareLogicException;
use Drupal\pipeline\Plugin\PipelineStepInterface;
use Drupal\pipeline\Plugin\PipelineStepPluginBase;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for Joinup ETL pipeline steps.
 */
abstract class JoinupFederationStepPluginBase extends PipelineStepPluginBase implements ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  /**
   * The SPARQL connection.
   *
   * @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface
   */
  protected $sparql;

  /**
   * The step's pipeline.
   *
   * We override the parent property just to provide a more specific type-hint.
   *
   * @var \Drupal\joinup_federation\JoinupFederationPipelineInterface
   */
  protected $pipeline;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new pipeline step plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql
   *   The SPARQL database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConnectionInterface $sparql, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sparql = $sparql;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): PipelineStepInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sparql_endpoint'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepare() {
    if ($error = parent::prepare()) {
      return $error;
    }

    // Try to refresh the lock on each step. Potentially, the current user can
    // time-out a step with form by postponing the submit until the pipeline
    // lock expires. This makes possible for a different user to start a new
    // import process that creates a new lock in their behalf. In this case we
    // have to abandon this pipeline.
    if (!$this->pipeline->lock()) {
      throw (new PipelineStepPrepareLogicException())->setError([
        '#markup' => $this->t("This import has timed-out. In the meantime another user has started a new import. Please come back later and retry."),
      ]);
    }

    // Ensure that the collection still exists and the URI is correct.
    $collection_id = $this->getPipeline()->getCollection();

    if (empty($collection_id) || empty($this->entityTypeManager->getStorage('rdf_entity')->load($collection_id))) {
      $exception = new PipelineStepPrepareLogicException("A collection with URI '{$collection_id}' does not exist.");
      // The error is for the user display. The exception message above is for
      // the tests.
      $exception->setError([
        '#markup' => $this->t("A collection with URI ':collection_id' does not exist.", [
          ':collection_id' => $collection_id,
        ]),
      ]);
      throw $exception;
    }
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
