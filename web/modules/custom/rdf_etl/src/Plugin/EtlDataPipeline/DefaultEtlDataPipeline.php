<?php

namespace Drupal\rdf_etl\Plugin\EtlDataPipeline;

use Drupal\rdf_etl\Plugin\EtlDataPipelineBase;
use Drupal\rdf_etl\Plugin\EtlDataPipelineInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rdf_etl\Plugin\EtlProcessStepInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rdf_etl\EtlOrchestrator;

/**
 * @EtlDataPipeline(
 *  id = "pipeline_selection_pipe",
 *  label = @Translation("The default data pipeline, allows selecting a data
 *   pipeline."),
 * )
 */
class DefaultEtlDataPipeline extends EtlDataPipelineBase implements EtlDataPipelineInterface, ContainerFactoryPluginInterface {

  /**
   * Drupal\rdf_etl\EtlOrchestrator definition.
   *
   * @var \Drupal\rdf_etl\EtlOrchestrator
   */
  protected $rdfEtlOrchestrator;

  /**
   * Constructs a new DefaultEtlDataPipeline object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\rdf_etl\EtlOrchestrator $rdf_etl_orchestrator
   *   The orchestrator.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EtlOrchestrator $rdf_etl_orchestrator
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->rdfEtlOrchestrator = $rdf_etl_orchestrator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('rdf_etl.orchestrator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Implement your logic.
    return $build;
  }

  public function getStepDefinitions() {
    return [
      'pipeline_selection_step' => [
        'pre_execute' => [$this, 'setAvailablePipelines'],
        'post_execute' => [$this, 'selectPipeline'],
      ],
    ];
  }

  public function setAvailablePipelines($data) {
    $data['options'] = array_map(function ($pipeline) {
      return $pipeline['label'];
    }, $this->rdfEtlOrchestrator->getPipelines());
    // Exclude ourselves to be selected.
    unset($data['options']['pipeline_selection_pipe']);
    return $data;
  }

  public function selectPipeline($data) {
    if (!isset($data['result'])) {
      throw new \Exception('No pipeline selected, but a pipeline is expected.');
    }
    $this->rdfEtlOrchestrator->setActivePipeline($data['result']);
  }

  public function executePipeline() {

  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    // Gets the plugin_id of the plugin instance.
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    // Gets the definition of the plugin implementation.
  }

}
