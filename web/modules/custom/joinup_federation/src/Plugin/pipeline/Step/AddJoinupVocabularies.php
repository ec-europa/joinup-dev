<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Plugin\PipelineStepWithClientRedirectResponseTrait;
use Drupal\pipeline\Plugin\PipelineStepWithResponseInterface;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface;
use Drupal\sparql_entity_storage\SparqlGraphStoreTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the Joinup vocabularies to the imported data.
 *
 * RDF imported data should use taxonomy terms already supported by Joinup
 * existing vocabularies. This step should run normally before the validation
 * step, in order to ensure that the incoming data refers Joinup set of terms.
 *
 * @PipelineStep(
 *   id = "add_joinup_vocabularies",
 *   label = @Translation("Add Joinup vocabularies"),
 * )
 */
class AddJoinupVocabularies extends JoinupFederationStepPluginBase implements PipelineStepWithResponseInterface {

  use PipelineStepWithClientRedirectResponseTrait;
  use SparqlGraphStoreTrait;

  /**
   * The SPARQL graph handler service.
   *
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface
   */
  protected $graphHandler;

  /**
   * Constructs a new plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql
   *   The SPARQL database connection.
   * @param \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface $graph_handler
   *   The SPARQL graph handler service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConnectionInterface $sparql, SparqlEntityStorageGraphHandlerInterface $graph_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->graphHandler = $graph_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sparql_endpoint'),
      $container->get('sparql.graph_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $query = [
      "ADD <{$this->getGraphUri('sink')}> TO <{$this->getGraphUri('sink_plus_taxo')}>;",
    ];
    foreach ($this->graphHandler->getEntityTypeGraphUris('taxonomy_term') as $graph_uri) {
      $query[] = "ADD <{$graph_uri['default']}> TO <{$this->getGraphUri('sink_plus_taxo')}>;";
    }
    $this->sparql->query(implode("\n", $query));
  }

}
