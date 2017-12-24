<?php

namespace Drupal\rdf_etl;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rdf_etl\Form\EtlOrchestratorForm;
use Drupal\rdf_etl\Plugin\EtlDataPipelineManager;
use Drupal\rdf_etl\Plugin\EtlProcessStepManager;

/**
 * Class EtlOrchestrator.
 */
class EtlOrchestrator {
  use StringTranslationTrait;
  /**
   * Drupal\rdf_etl\Plugin\EtlDataPipelineManager definition.
   *
   * @var \Drupal\rdf_etl\Plugin\EtlDataPipelineManager
   */
  protected $pluginManagerEtlDataPipeline;
  /**
   * Drupal\rdf_etl\Plugin\EtlProcessStepManager definition.
   *
   * @var \Drupal\rdf_etl\Plugin\EtlProcessStepManager
   */
  protected $pluginManagerEtlProcessStep;

  /**
   * The persistent state of the importer.
   *
   * @var \Drupal\rdf_etl\EtlStateManager
   */
  protected $stateManager;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  protected $form = [];

  /**
   * Constructs a new EtlOrchestrator object.
   */
  public function __construct(EtlDataPipelineManager $plugin_manager_etl_data_pipeline, EtlProcessStepManager $plugin_manager_etl_process_step, EtlStateManager $state_manager, FormBuilderInterface $form_builder) {
    $this->pluginManagerEtlDataPipeline = $plugin_manager_etl_data_pipeline;
    $this->pluginManagerEtlProcessStep = $plugin_manager_etl_process_step;
    $this->stateManager = $state_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * Blaat.
   *
   * @return array|mixed[]|null
   *   The defined data pipelines.
   */
  public function getPipelines() {
    return $this->pluginManagerEtlDataPipeline->getDefinitions();
  }

  protected function isActive() {
    return !empty($this->stateManager->getImmutable()->step);
  }

  protected function selectDataPipeline() {
    $this->stateManager->setActiveStep('pipeline_selection_step');
  }

  /**
   * Execute the orchestrator.
   *
   * @return array
   *   Render array.
   */
  public function run() {
    if (!$this->isActive()) {
      return $this->selectDataPipeline();
    }

    $active_step = $this->stateManager->getImmutable()->step;
    $active_pipeline = $this->stateManager->getImmutable()->pipeline;
    $next_step = $this->executeStep($active_step, $active_pipeline);
    $this->stateManager->setActiveStep($next_step);

    return $this->form;
  }

  protected function executeStep($active_process_step, $pipeline = NULL) {
    $form_state = new FormState();
    $configuration = ['orchestrator' => $this];
    $active_process_step_plugin = $this->pluginManagerEtlProcessStep->createInstance($active_process_step, $configuration);
    $next_process_step = $active_process_step;

    if ($active_process_step_plugin instanceof PluginFormInterface) {
      $form_state->addBuildInfo('active_process_step', $active_process_step_plugin);
      $this->form = $this->formBuilder->buildForm(EtlOrchestratorForm::class, $form_state);
      if (isset($form_state->getBuildInfo()['next_step'])) {
        $next_process_step = $form_state->getBuildInfo()['next_step'];
      }
    }
    else {
      $next_process_step = $active_process_step_plugin->execute();
    }
    return $next_process_step;
  }

}
