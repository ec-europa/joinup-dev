<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rdf_etl\Form\EtlOrchestratorForm;
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
    $current_state = $this->initializeActiveState();
    $new_state = $this->executeStep($current_state);
    $this->stateManager->setState($new_state);

    return $this->response;
  }

  /**
   * Controller callback: Reset the state machine.
   *
   * Should not be used, unless something went really bad.
   */
  public function reset(): void {
    $this->stateManager->reset();
  }

  /**
   * Gets the active process step.
   *
   * @return \Drupal\rdf_etl\Plugin\EtlProcessStepInterface
   *   The active process step.
   */
  protected function getStepInstance(EtlState $state): EtlProcessStepInterface {
    $plugin_id = $this->pipeline->stepDefinitionList()->get($state->sequence())->getPluginId();
    return $this->pluginManagerEtlProcessStep->createInstance($plugin_id);
  }

  /**
   * Initialize the state machine from the persisted state.
   */
  protected function initializeActiveState(): EtlState {
    if (!$this->stateManager->isPersisted()) {
      // Initialize to default pipeline.
      $active_state = new EtlState(
        self::DEFAULT_PIPELINE,
        self::FIRST_STEP
      );
    }
    else {
      $active_state = $this->stateManager->state();
    }
    $this->pipeline = $this->pluginManagerEtlDataPipeline->createInstance($active_state->pipelineId());
    // Restore the active pipeline from the persistent store.
    $this->pipeline->stepDefinitionList()->seek($active_state->sequence());
    return $active_state;
  }

  /**
   * Progress the state machine with one step.
   *
   * @param \Drupal\rdf_etl\EtlState $current_state
   *   The current state.
   *
   * @return \Drupal\rdf_etl\EtlState
   *   The next state.
   */
  protected function executeStep(EtlState $current_state): EtlState {
    $data = [];
    $data['state'] = $current_state;

    $data = $this->stepDefinition($current_state)->invokeHook('pre_form_execution', $data);
    $form_state = new FormState();
    $data = $this->buildForm($current_state, $form_state, $data);

    // In case of validation errors, or a rebuild (e.g. multi step), bail out.
    if (!$form_state->isExecuted()) {
      return $current_state;
    }

    $data['state'] = $this->getNextState($current_state);
    $data = $this->stepDefinition($current_state)->invokeHook('post_form_execution', $data);
    $this->getStepInstance($current_state)->execute($data);
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

  /**
   * Gets the a step definition from the list by the offset given in the state.
   *
   * @param \Drupal\rdf_etl\EtlState $state
   *   The state for which to get the step definition.
   *
   * @return PipelineStepDefinitionInterface
   *   The step definition.
   */
  protected function stepDefinition(EtlState $state): PipelineStepDefinitionInterface {
    return $this->pipeline->stepDefinitionList()->get($state->sequence());
  }

  /**
   * Build the state object that points to the next step in the pipeline.
   *
   * @return \Drupal\rdf_etl\EtlState
   *   The next State.
   */
  protected function getNextState(EtlState $state): EtlState {
    $this->pipeline->stepDefinitionList()->seek($state->sequence());
    $this->pipeline->stepDefinitionList()->next();
    $next_state = new EtlState($state->pipelineId(), self::FINAL_STEP);
    if ($this->pipeline->stepDefinitionList()->valid()) {
      $next_state = new EtlState($state->pipelineId(), $this->pipeline->stepDefinitionList()->key());
    }
    return $next_state;
  }

  /**
   * Builds the form.
   *
   * @param \Drupal\rdf_etl\EtlState $current_state
   *   The current state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $data
   *   The data array.
   *
   * @return array
   *   The data array.
   */
  protected function buildForm(EtlState $current_state, FormStateInterface &$form_state, array $data): array {
    $active_step_plugin_id = $this->pipeline->stepDefinitionList()->get($current_state->sequence())->getPluginId();
    $form_state->addBuildInfo('active_process_step', $active_step_plugin_id);
    $form_state->addBuildInfo('data', $data);
    $this->response = $this->formBuilder->buildForm(EtlOrchestratorForm::class, $form_state);
    return $form_state->getBuildInfo()['data'];
  }

}
