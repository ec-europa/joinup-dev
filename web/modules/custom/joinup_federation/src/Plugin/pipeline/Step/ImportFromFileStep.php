<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Exception\PipelineStepExecutionLogicException;
use Drupal\pipeline\Plugin\PipelineStepInterface;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Drupal\sparql_entity_storage\SparqlGraphStoreTrait;
use EasyRdf\Graph;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a step plugin that imports data from a local or remote file.
 *
 * The 'resource' configuration should be provided, to point to a local file or
 * URL If a URL is provided, data will be fetched via an HTTP GET request.
 *
 * @PipelineStep(
 *   id = "file_import",
 *   label = @Translation("Import from file"),
 * )
 */
class ImportFromFileStep extends JoinupFederationStepPluginBase {

  use SparqlGraphStoreTrait;

  /**
   * The HTTP client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

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
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConnectionInterface $sparql, EntityTypeManagerInterface $entity_type_manager, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql, $entity_type_manager);
    $this->httpClient = $http_client;
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
      $container->get('entity_type.manager'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // The resource from where to load the data to be imported. It could be a
      // local file or a URL. In the case of an URL, a HTTP GET will be issued.
      'resource' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (!$resource = $this->getConfiguration()['resource']) {
      throw new \Exception("Step 'file_import' called without configuring a file or URL.");
    }
    $graph = new Graph($this->getGraphUri('sink'));

    // Local file.
    if (file_exists($resource)) {
      if (!is_readable($resource)) {
        throw new PipelineStepExecutionLogicException("Cannot read from file: '{$resource}'.");
      }
      $graph->parseFile($resource);
    }
    // Remote resource (URL).
    elseif (UrlHelper::isValid($resource, TRUE) && UrlHelper::isExternal($resource)) {
      $response = $this->httpClient->request('GET', $resource, ['http_errors' => FALSE]);
      if ($response->getStatusCode() !== 200) {
        throw new PipelineStepExecutionLogicException("Cannot read data from '{$resource}' (received code {$response->getStatusCode()}).");
      }
      $graph->parse($response->getBody()->getContents());
    }
    else {
      throw new \InvalidArgumentException("Invalid resource: '{$resource}'.");
    }

    $this->createGraphStore()->insert($graph);
  }

}
