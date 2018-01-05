<?php

namespace Drupal\rdf_etl;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rdf_etl\Form\EtlOrchestratorForm;
use Drupal\rdf_etl\Plugin\EtlDataPipelineInterface;
use Drupal\rdf_etl\Plugin\EtlDataPipelineManager;
use Drupal\rdf_etl\Plugin\EtlProcessStepInterface;
use Drupal\rdf_etl\Plugin\EtlProcessStepManager;

/**
 * Class EtlOrchestrator.
 */
class EtlOrchestrator {

  const DEFAULT_PIPELINE = 'pipeline_selection_pipe';

  const DEFAULT_STEP = 'pipeline_selection_step';

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

  public function reset() {
    $this->stateManager->reset();
    return ['#markup' => 'Orchestrator reset.'];
  }

  public function setActivePipeline($pipeline = NULL) {
    if (!$pipeline) {
      $pipeline = self::DEFAULT_PIPELINE;
    }
    $this->stateManager->setActivePipeline($pipeline);
    $steps = $this->getActivePipeline()->getStepDefinitions();
    if (is_array(current($steps))) {
      $first_step = key($steps);
    }
    else {
      $first_step = current($steps);
    }

    $this->stateManager->setActiveStep($first_step);
  }

  /**
   * Execute the orchestrator.
   *
   * @return array
   *   Render array.
   */
  public function run() {
    if (!$this->stateManager->isInitialized()) {
      $this->setActivePipeline();
    }

    $active_step = $this->getActiveStep();
    $active_pipeline = $this->getActivePipeline();

    $next_step = $this->executeStep($active_step, $active_pipeline);
    $this->stateManager->setActiveStep($next_step);

    return $this->form;
  }

  /**
   * Gets the active process step.
   *
   * @return \Drupal\rdf_etl\Plugin\EtlProcessStepInterface
   *   The active process step.
   */
  public function getActiveStep() : EtlProcessStepInterface {
    $active_step = $this->stateManager->getActiveStep();
    return $this->pluginManagerEtlProcessStep->createInstance($active_step);
  }

  /**
   * Gets the active data pipeline.
   *
   * @return \Drupal\rdf_etl\Plugin\EtlDataPipelineInterface
   *   The active data pipeline.
   */
  protected function getActivePipeline() : EtlDataPipelineInterface {
    $active_pipeline = $this->stateManager->getActivePipeline();
    return $this->pluginManagerEtlDataPipeline->createInstance($active_pipeline);
  }

  protected function getActiveStepDefinition() {
    $steps = $this->getPipelineStepDefinitions();
    $active_step_id = $this->getActiveStep()->getPluginId();
    if (!isset($steps[$active_step_id])) {
      throw new \Exception($this->t('Step %step not defined in pipeline %pipeline', ['%step' => $active_step_id, '%pipeline' => $this->getActivePipeline()->getPluginId()]));
    }
    return $steps[$active_step_id];
  }

  protected function callPipelineHook(string $method_name, array $data) {
    $definition = $this->getActiveStepDefinition();
    if (!isset($definition[$method_name])) {
      // The pipeline does not implement this method.
      return $data;
    }

    if (!is_callable($definition[$method_name])) {
      throw new \Exception($this->t('Pipeline defines a callback for but does not implement it.'));
    }
    return call_user_func_array($definition[$method_name], [$data]);
  }

  protected function getPipelineStepDefinitions() {
    return $this->getActivePipeline()->getStepDefinitions();


  }

  protected function nextStep() {
    $current = $this->getActiveStep()->getPluginId();
    //$next =


  }

  /**
   * Execute a step.
   *
   * @param \Drupal\rdf_etl\Plugin\EtlProcessStepInterface $active_process_step
   *   The current step.
   * @param \Drupal\rdf_etl\Plugin\EtlDataPipelineInterface $pipeline
   *   The current pipeline.
   *
   * @return string
   *   The id of the next data step to execute.
   */
  protected function executeStep(EtlProcessStepInterface $active_process_step, EtlDataPipelineInterface $pipeline) {
    $form_state = new FormState();

    $next_process_step = $active_process_step->getPluginId();
    $data = [];
    $data = $this->callPipelineHook('pre_execute', $data);
    if ($active_process_step instanceof PluginFormInterface) {
      $form_state->addBuildInfo('active_process_step', $active_process_step->getPluginId());
      $form_state->addBuildInfo('data', $data);
      $this->form = $this->formBuilder->buildForm(EtlOrchestratorForm::class, $form_state);
      $data = $form_state->getBuildInfo()['data'];
      if (isset($form_state->getBuildInfo()['next_step'])) {
        $next_process_step = $form_state->getBuildInfo()['next_step'];
      }
    }
    else {
      $next_process_step = $active_process_step->execute();
    }
    if ($form_state->isExecuted()) {
      $this->callPipelineHook('post_execute', $data);
    }
    return $next_process_step;
  }

}
