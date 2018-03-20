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
  public function execute(array &$data): void {
    $this->clearExistingData();
    try {
      $this->createGraphStore()->replace($data['graph'], $this->getConfiguration()['sink_graph']);
    }
    catch (\Exception $exception) {
      $data['error'] = $this->t('Could not store triples in triple store. Reason @message', [
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

    $build_info = $form_state->getBuildInfo();
    $data = &$build_info['data'];

    $adms_file = $form_state->getValue('adms_file');
    if (!isset($adms_file[0]) || !$file = File::load($adms_file[0])) {
      $form_state->setError($form['adms_file'], 'Please upload a valid RDF file.');
      return;
    }
    try {
      $data['graph'] = $this->fileToGraph($file);
    }
    catch (\Exception $e) {
      $form_state->setError($form['adms_file'], 'The provided file is not a valid RDF file.');
    }

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
    if ($file = File::load($form_state->getValue('adms_file')[0])) {
      $file->delete();
    }
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
