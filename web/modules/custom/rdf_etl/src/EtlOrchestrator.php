<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
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

  const FIRST_STEP = 0;

  const FINAL_STEP = -1;

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

  protected $response = [];

  /**
   * The active pipeline.
   *
   * @var \Drupal\rdf_etl\Plugin\EtlDataPipelineInterface
   */
  protected $pipeline;

  /**
   * The current state of the state machine.
   *
   * @var \Drupal\rdf_etl\EtlState
   */
  protected $activeState;

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
    $this->initializeActiveState();

    $active_step = $this->getActiveStep();

    $state = $this->executeStep($active_step);
    $this->stateManager->setState($state);

    return $this->response;
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
    $plugin_id = $this->pipeline->stepDefinitionList()->get($this->activeState->sequence())->getPluginId();
    return $this->pluginManagerEtlProcessStep->createInstance($plugin_id);
  }

  /**
   * Gets the active data pipeline.
   *
   * @return \Drupal\rdf_etl\Plugin\EtlDataPipelineInterface
   *   The active data pipeline.
   */
  protected function getActivePipeline(): EtlDataPipelineInterface {
    $active_pipeline = $this->stateManager->state()->pipelineId();
    return $this->pluginManagerEtlDataPipeline->createInstance($active_pipeline);
  }

  /**
   * Initialize the state machine from the persisted state.
   */
  protected function initializeActiveState() {
    if (!$this->stateManager->isPersisted()) {
      // Initialize to default pipeline.
      $this->activeState = new EtlState(
        self::DEFAULT_PIPELINE,
        self::FIRST_STEP
      );
    }
    else {
      $this->activeState = $this->stateManager->state();
    }
    $this->pipeline = $this->pluginManagerEtlDataPipeline->createInstance($this->activeState->pipelineId());
    // Restore the active pipeline from the persistent store.
    $this->pipeline->stepDefinitionList()->seek($this->activeState->sequence());
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
  protected function callPipelineHook(string $hook, array $data): array {
    $definition = $this->pipeline->stepDefinitionList()->get($this->activeState->sequence());
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
    $return = call_user_func_array($callback, [$data]);
    if (empty($return)) {
      $callback_name = get_class(current($callback)) . '::' . end($callback) . '()';
      throw new \Exception("Callback $callback_name should return the data array.");
    }
    return $return;
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
  protected function executeStep(EtlProcessStepInterface $active_process_step): EtlState {
    $form_state = new FormState();

    $data = [];
    $current_state = new EtlState($this->pipeline->getPluginId(), $this->pipeline->stepDefinitionList()->current()->getPluginId());
    $data['state'] = $current_state;
    $data = $this->callPipelineHook('pre_execute', $data);
    $form_state->addBuildInfo('active_process_step', $active_process_step->getPluginId());
    $form_state->addBuildInfo('data', $data);
    $this->response = $this->formBuilder->buildForm(EtlOrchestratorForm::class, $form_state);
    $data = $form_state->getBuildInfo()['data'];

    // In case of validation errors, or a rebuild (e.g. multi step), bail out.
    if (!$form_state->isExecuted()) {
      return $current_state;
    }

    $this->pipeline->stepDefinitionList()->next();
    $data['state'] = new EtlState($this->pipeline->getPluginId(), self::FINAL_STEP);
    if ($this->pipeline->stepDefinitionList()->valid()) {
      $data['state'] = new EtlState($this->pipeline->getPluginId(), $this->pipeline->stepDefinitionList()->key());
    }
    $data = $this->callPipelineHook('post_execute', $data);
    $active_process_step->execute($data);
    $this->redirectForm($form_state);
    return $data['state'];
  }

  /**
   * Reload the page if the form needs to rebuild.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function redirectForm(FormStateInterface $form_state): void {
    $form_state->disableRedirect(FALSE);

    /** @var \Drupal\Core\Form\FormBuilder $form_builder */
    $form_builder = $this->formBuilder;
    // @todo Depending on the implementation: method not present in interface.
    $redirect = $form_builder->redirectForm($form_state);
    if ($redirect) {
      $this->response = $redirect;
    }
  }

}
