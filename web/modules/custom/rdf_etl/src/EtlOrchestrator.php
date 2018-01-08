<?php

namespace Drupal\rdf_etl;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\PluginFormInterface;
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

  /** @var  \Drupal\rdf_etl\Plugin\EtlDataPipelineInterface */
  protected $pipeline;

  /** @var \Drupal\rdf_etl\Plugin\EtlProcessStepInterface */
  protected $active_step;

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

  /**
   * Gets the active data pipeline.
   *
   * @return \Drupal\rdf_etl\Plugin\EtlDataPipelineInterface
   *   The active data pipeline.
   */
  protected function getActivePipeline(): EtlDataPipelineInterface {
    $active_pipeline = $this->stateManager->getPersistedPipeline();
    return $this->pluginManagerEtlDataPipeline->createInstance($active_pipeline);
  }

  public function setActivePipeline($pipeline_id = NULL) {
    if (!$pipeline_id) {
      $pipeline_id = self::DEFAULT_PIPELINE;
    }
    $this->stateManager->setState($pipeline_id, 0);
    $this->pipeline = $this->pluginManagerEtlDataPipeline->createInstance($pipeline_id);
  }

  /**
   * Execute the orchestrator.
   *
   * @return array
   *   Render array.
   */
  public function run() {
    $this->initializePipelineFromPersistenState();

    $active_step = $this->getActiveStep();

    $next_step_id = $this->executeStep($active_step);
    $this->persistState($next_step_id);


    return $this->form;
  }

  protected function persistState($step) {
    if (empty($this->pipeline->getPluginId())) {
      throw new \Exception('Pipeline not set');
    }
    $this->stateManager->setState($this->pipeline->getPluginId(), $step);
  }

  protected function initializePipelineFromPersistenState() {
    if (!$this->stateManager->isPersisted()) {
      // Initialize to default pipeline.
      $this->setActivePipeline();
      return;
    }
    // Restore the active pipeline from the persistent store.
    $this->setActivePipeline($this->stateManager->getPersistedPipeline());
    $this->active_step = $this->stateManager->getPersistedStep();
    $this->pipeline->getSteps()->seek($this->active_step);
  }

  /**
   * Gets the active process step.
   *
   * @return \Drupal\rdf_etl\Plugin\EtlProcessStepInterface
   *   The active process step.
   */
  public function getActiveStep(): EtlProcessStepInterface {
    $plugin_id = $this->pipeline->getSteps()->get($this->active_step)->getPluginId();
    return $this->pluginManagerEtlProcessStep->createInstance($plugin_id);
  }

  protected function getActiveStepDefinition() : PipelineStepDefinition {
    return $this->pipeline->getSteps()->get($this->active_step);
  }

  /**
   * Invoke a hook on the data pipeline.
   *
   * @param string $hook
   *   The hook name.
   * @param array $data
   *   The data array.
   *
   * @return array|mixed
   * @throws \Exception
   */
  protected function callPipelineHook(string $hook, array $data) {
    $definition = $this->getActiveStepDefinition();
    switch ($hook) {
      case 'pre_execute':
        $callback = $definition->getPreExecute();
        break;

      case 'post_execute':
        $callback = $definition->getPostExecute();
        break;

      default:
        throw new \Exception('Unsupported hook.');
    }
    if (empty($callback)) {
      // The pipeline does not implement this method.
      return $data;
    }

    if (!is_callable($callback)) {
      throw new \Exception($this->t('Pipeline defines a callback for but does not implement it.'));
    }
    return call_user_func_array($callback, [$data]);
  }

  protected function getPipelineStepDefinitions() {
    return $this->getActivePipeline()->getSteps();


  }

  /**
   * Execute a step.
   *
   * @param \Drupal\rdf_etl\Plugin\EtlProcessStepInterface $active_process_step
   *   The current step.
   *
   * @return string
   *   The id of the next data step to execute.
   */
  protected function executeStep(EtlProcessStepInterface $active_process_step) {
    $form_state = new FormState();

    $data = [];
    $data = $this->callPipelineHook('pre_execute', $data);
    if ($active_process_step instanceof PluginFormInterface) {
      $form_state->addBuildInfo('active_process_step', $active_process_step->getPluginId());
      $form_state->addBuildInfo('data', $data);
      $this->form = $this->formBuilder->buildForm(EtlOrchestratorForm::class, $form_state);
      $data = $form_state->getBuildInfo()['data'];
    }
    $data['next_step'] = $this->pipeline->getSteps()->current()->getPluginId();
    if ($form_state->isExecuted()) {
      $this->pipeline->getSteps()->next();
      if ($this->pipeline->getSteps()->valid()) {
        $data['next_step'] = $this->pipeline->getSteps()->current()->getPluginId();
      }
      else {
        $data['next_step'] = 'finished';
      }

      $this->callPipelineHook('post_execute', $data);
    }
    return $data['next_step'];
  }

}
