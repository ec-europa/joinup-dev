<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin\EtlProcessStep;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\file\FileInterface;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_etl\ProcessStepBase;
use EasyRdf\Graph;
use EasyRdf\GraphStore;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a manual data upload step plugin.
 *
 * @EtlProcessStep(
 *  id = "manual_upload_step",
 *  label = @Translation("Manual upload"),
 * )
 */
class ManualUploadStep extends ProcessStepBase implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * The graph store.
   *
   * @var \EasyRdf\GraphStore
   */
  protected $graphStore;

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
   * Creates a new 'manual_upload_step' process step plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql_connection
   *   The SPARQL database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $sparql_connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->sparqlConnection = $sparql_connection;
    $connection_options = $sparql_connection->getConnectionOptions();
    $connect_string = 'http://' . $connection_options['host'] . ':' . $connection_options['port'] . '/sparql-graph-crud';
    // Use a local SPARQL 1.1 Graph Store.
    $this->graphStore = new GraphStore($connect_string);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array $data): void {
    /** @var \EasyRdf\Http\Response $response */
    $response = $this->graphStore->replace($data['graph'], $this->getConfiguration()['sink_graph']);
    if (!$response->isSuccessful()) {
      $data['error'] = 'Could not store triples in triple store.';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['adms_file'] = [
      '#type' => 'file',
      '#title' => $this->t('File'),
      '#description' => $this->t('Please upload a file to use for federation. Allowed types: @extensions.', [
        '@extensions' => 'rdf ttl',
      ]),
      '#upload_validators'  => [
        'file_validate_extensions' => ['rdf ttl'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::validateConfigurationForm($form, $form_state);
    $build_info = $form_state->getBuildInfo();
    $data = &$build_info['data'];
    $file = $this->uploadedFile();
    if (!$file) {
      $form_state->setError($form['adms_file'], 'Please upload a valid RDF file.');
      return;
    }
    try {
      $data['graph'] = $this->fileToGraph($file);
    }
    catch (\Exception $e) {
      $form_state->setError($form['adms_file'], 'The provided file is not a valid RDF file.');
    }
    // Delete the uploaded file from disk.
    $file->delete();

    $form_state->setBuildInfo($build_info);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $build_info = $form_state->getBuildInfo();
    $data = &$build_info['data'];
    $data['result'] = $form_state->getValue('adms_file');
    $form_state->setBuildInfo($build_info);
  }

  /**
   * Retrieves the uploaded file.
   *
   * @return \Drupal\file\FileInterface|null
   *   File object, if one is uploaded.
   */
  protected function uploadedFile(): ?FileInterface {
    $files = file_save_upload('data', ['file_validate_extensions' => [0 => 'rdf ttl']], 'public://');
    /** @var \Drupal\file\FileInterface $file */
    $file = $files[0];
    if (!is_object($file)) {
      return NULL;
    }
    return $file;
  }

  /**
   * Builds a RDF graph from a file object.
   *
   * @param \Drupal\file\FileInterface $file
   *   The to be validated file.
   *
   * @return \EasyRdf\Graph
   *   A collection of triples.
   */
  protected function fileToGraph(FileInterface $file): Graph {
    $graph = new Graph();
    $graph->parseFile($file->getFileUri());
    return $graph;
  }

}
