<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin\EtlDataPipeline;

use Drupal\rdf_etl\EtlState;
use Drupal\rdf_etl\PipelineStepDefinitionList;
use Drupal\rdf_etl\Plugin\EtlDataPipelineBase;
use Drupal\rdf_etl\Plugin\EtlDataPipelineInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rdf_etl\Plugin\EtlDataPipelineManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rdf_etl\EtlOrchestrator;

/**
 * A pseudo data pipeline.
 *
 * Used to provide a selection mechanism for the actual pipeline.
 *
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
   * The pipeline plugin manager service.
   *
   * @var \Drupal\rdf_etl\Plugin\EtlDataPipelineManager
   */
  protected $pipelineManager;

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
   * @param \Drupal\rdf_etl\Plugin\EtlDataPipelineManager $pipeline_manager
   *   The data pipeline manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EtlOrchestrator $rdf_etl_orchestrator,
    EtlDataPipelineManager $pipeline_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->rdfEtlOrchestrator = $rdf_etl_orchestrator;
    $this->pipelineManager = $pipeline_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('rdf_etl.orchestrator'),
      $container->get('plugin.manager.etl_data_pipeline')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function initStepDefinition(): void {
    $this->steps = new PipelineStepDefinitionList();
    $this->steps->add('pipeline_selection_step')
      ->setPreExecute([$this, 'setAvailablePipelines'])
      ->setPostExecute([$this, 'selectPipeline']);
  }

  /**
   * Pre-execute callback: Sets the available pipelines as options in the form.
   */
  public function setAvailablePipelines($data): array {
    $data['options'] = array_map(function ($pipeline) {
      return $pipeline['label'];
    }, $this->pipelineManager->getDefinitions());
    // Exclude ourselves to be selected.
    unset($data['options']['pipeline_selection_pipe']);
    return $data;
  }

  /**
   * Post-execute callback: Sets the active pipeline to the one selected.
   *
   * @param array $data
   *   The data array from the form.
   *
   * @see \Drupal\rdf_etl\Plugin\EtlProcessStep\PipelineSelectionStep
   *
   * @throws \Exception
   */
  public function selectPipeline(array $data): array {
    if (!isset($data['result'])) {
      throw new \Exception('No pipeline selected, but a pipeline is expected.');
    }
    $data['state'] = new EtlState($data['result'], EtlOrchestrator::FIRST_STEP);
    return $data;
  }

}
