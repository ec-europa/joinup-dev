<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin\rdf_etl\Step;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_entity\RdfEntityGraphStoreTrait;
use Drupal\rdf_etl\Plugin\RdfEtlStepWithFormPluginBase;
use EasyRdf\Graph;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a manual data upload step plugin.
 *
 * @RdfEtlStep(
 *  id = "manual_upload_step",
 *  label = @Translation("Manual upload"),
 * )
 */
class ManualUploadStep extends RdfEtlStepWithFormPluginBase implements ContainerFactoryPluginInterface {

  use RdfEntityGraphStoreTrait;

  /**
   * The SPARQL connection.
   *
   * @var \Drupal\rdf_entity\Database\Driver\sparql\Connection
   */
  protected $sparqlConnection;

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
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array &$data) {
    $this->clearExistingData();
    try {
      $this->createGraphStore()->replace($data['graph'], $this->getConfiguration()['sink_graph']);
      $data['adms_file']->delete();
    }
    catch (\Exception $exception) {
      return $this->t('Could not store triples in triple store. Reason: @message', [
        '@message' => $exception->getMessage(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['adms_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('File'),
      '#description' => $this->t('Please upload a file to use for federation. Allowed types: @extensions.', [
        '@extensions' => '*.rdf, *.ttl',
      ]),
      '#upload_validators'  => [
        'file_validate_extensions' => ['rdf ttl'],
      ],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::validateConfigurationForm($form, $form_state);

    if (!$file = $this->getFile($form_state)) {
      $form_state->setError($form['adms_file'], 'Please upload a valid RDF file.');
      return;
    }

    try {
      $form_state->set('adms_file', $file);
      $form_state->set('graph', $this->fileToGraph($file));
    }
    catch (\Exception $e) {
      $form_state->setError($form['adms_file'], 'The provided file is not a valid RDF file.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function extractDataFromSubmit(FormStateInterface $form_state): array {
    return [
      'adms_file' => $form_state->get('adms_file'),
      'graph' => $form_state->get('graph'),
    ];
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

  /**
   * Returns the file entity from the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\file\FileInterface|null
   *   The uploaded file entity.
   */
  protected function getFile(FormStateInterface $form_state): ?FileInterface {
    $adms_files = $form_state->getValue('adms_file');
    if (!isset($adms_files[0]) || !$file = File::load($adms_files[0])) {
      return NULL;
    }
    return $file;
  }

  /**
   * Checks the backend for existing data in the sink graph.
   *
   * @throws \Exception
   *   If the SPARQL query is failing.
   */
  protected function clearExistingData(): void {
    $graph_uri = $this->getConfiguration()['sink_graph'];
    $this->sparqlConnection->update("CLEAR GRAPH <$graph_uri>");
  }

}
