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

  /**
   * The active pipeline.
   *
   * @var \Drupal\rdf_etl\Plugin\EtlDataPipelineInterface
   */
  protected $pipeline;

  /**
   * The data pipeline sequence.
   *
   * @var int
   */
  protected $activePipelineSequence;

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
   * Execute the orchestrator.
   *
   * @return array
   *   Render array.
   */
  public function run() {
    $this->initializePipelineFromPersistentState();

    $active_step = $this->getActiveStep();

    $next_pipeline_sequence = $this->executeStep($active_step);
    $this->persistState($next_pipeline_sequence);

    return $this->form;
  }

  /**
   * Controller callback: Reset the state machine.
   *
   * Should not be used, unless something went really bad.
   */
  public function reset() {
    $this->stateManager->reset();
  }

  /**
   * Gets the active process step.
   *
   * @return \Drupal\rdf_etl\Plugin\EtlProcessStepInterface
   *   The active process step.
   */
  public function getActiveStep(): EtlProcessStepInterface {
    $plugin_id = $this->pipeline->stepDefinitionList()->get($this->activePipelineSequence)->getPluginId();
    return $this->pluginManagerEtlProcessStep->createInstance($plugin_id);
  }

  /**
   * Gets the active data pipeline.
   *
   * @return \Drupal\rdf_etl\Plugin\EtlDataPipelineInterface
   *   The active data pipeline.
   */
  protected function getActivePipeline(): EtlDataPipelineInterface {
    $active_pipeline = $this->stateManager->getPersistedPipelineId();
    return $this->pluginManagerEtlDataPipeline->createInstance($active_pipeline);
  }

  /**
   * Persist the the active pipeline and step across requests.
   *
   * @param int $sequence
   *   The position of the pipeline.
   *
   * @throws \Exception
   */
  protected function persistState(int $sequence) {
    if (empty($this->pipeline->getPluginId())) {
      throw new \Exception('Pipeline not set');
    }
    $this->stateManager->setState($this->pipeline->getPluginId(), $sequence);
  }

  /**
   * Initialize the state machine from the persisted state.
   */
  protected function initializePipelineFromPersistentState() {
    if (!$this->stateManager->isPersisted()) {
      // Initialize to default pipeline.
      $this->setActivePipeline();
      return;
    }
    // Restore the active pipeline from the persistent store.
    $this->setActivePipeline($this->stateManager->getPersistedPipelineId());
    $this->activePipelineSequence = $this->stateManager->getPersistedPipelineSequence();
    $this->pipeline->stepDefinitionList()->seek($this->activePipelineSequence);
  }

  /**
   * Sets the pipeline to use for execution.
   *
   * @param string $pipeline_id
   *   The plugin id of the pipeline.
   */
  public function setActivePipeline(string $pipeline_id = NULL) {
    if (!$pipeline_id) {
      $pipeline_id = self::DEFAULT_PIPELINE;
    }
    $this->stateManager->setState($pipeline_id, 0);
    $this->pipeline = $this->pluginManagerEtlDataPipeline->createInstance($pipeline_id);
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
   *   The data array.
   *
   * @throws \Exception
   */
  protected function callPipelineHook(string $hook, array $data) : array {
    $definition = $this->pipeline->stepDefinitionList()->get($this->activePipelineSequence);
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

  /**
   * Execute a step.
   *
   * @param \Drupal\rdf_etl\Plugin\EtlProcessStepInterface $active_process_step
   *   The current step.
   *
   * @return string
   *   The id of the next data step to execute.
   */
  protected function executeStep(EtlProcessStepInterface $active_process_step) : string {
    $form_state = new FormState();

    $data = [];
    $data = $this->callPipelineHook('pre_execute', $data);
    if ($active_process_step instanceof PluginFormInterface) {
      $form_state->addBuildInfo('active_process_step', $active_process_step->getPluginId());
      $form_state->addBuildInfo('data', $data);
      $this->form = $this->formBuilder->buildForm(EtlOrchestratorForm::class, $form_state);
      $data = $form_state->getBuildInfo()['data'];
    }
    $data['next_step'] = $this->pipeline->stepDefinitionList()->current()->getPluginId();
    if ($form_state->isExecuted()) {
      $this->pipeline->stepDefinitionList()->next();
      if ($this->pipeline->stepDefinitionList()->valid()) {
        $data['next_step'] = $this->pipeline->stepDefinitionList()->current()->getPluginId();
      }
      else {
        $data['next_step'] = 'finished';
      }

      $this->callPipelineHook('post_execute', $data);
    }
    return $data['next_step'];
  }

}
