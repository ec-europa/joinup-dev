<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rdf_etl\Form\RdfEtlOrchestratorForm;
use Drupal\rdf_etl\Plugin\RdfEtlPipelinePluginManager;
use Drupal\rdf_etl\Plugin\RdfEtlStepInterface;
use Drupal\rdf_etl\Plugin\RdfEtlStepPluginManager;
use Drupal\rdf_etl\Plugin\RdfEtlStepWithFormInterface;

/**
 * The RDF ETL Orchestrator.
 *
 * The orchestrator uses a simple state machine to keep track of progress and
 * coordinates the work. The actual work is performed by plugins.
 */
class RdfEtlOrchestrator implements RdfEtlOrchestratorInterface {

  use StringTranslationTrait;

  /**
   * The first step id.
   */
  const FIRST_STEP = 0;

  /**
   * The last step id.
   */
  const FINAL_STEP = -1;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormSubmitterInterface
   */
  protected $formBuilder;

  /**
   * The active pipeline.
   *
   * @var \Drupal\rdf_etl\Plugin\RdfEtlPipelineInterface
   */
  protected $pipeline;

  /**
   * The EtlDataPipelineManager plugin manager.
   *
   * @var \Drupal\rdf_etl\Plugin\RdfEtlPipelinePluginManager
   */
  protected $pipelinePluginManager;

  /**
   * The EtlProcessStepManager plugin manager.
   *
   * @var \Drupal\rdf_etl\Plugin\RdfEtlStepPluginManager
   */
  protected $stepPluginManager;

  /**
   * The response value.
   *
   * @var mixed
   */
  protected $response;

  /**
   * The persistent state of the importer.
   *
   * @var \Drupal\rdf_etl\RdfEtlStateManager
   */
  protected $stateManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new EtlOrchestrator object.
   *
   * @param \Drupal\rdf_etl\Plugin\RdfEtlPipelinePluginManager $pipeline_plugin_manager
   *   The EtlDataPipelineManager plugin manager.
   * @param \Drupal\rdf_etl\Plugin\RdfEtlStepPluginManager $step_plugin_manager
   *   The EtlProcessStepManager plugin manager.
   * @param \Drupal\rdf_etl\RdfEtlStateManager $state_manager
   *   The persistent state of the importer.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(RdfEtlPipelinePluginManager $pipeline_plugin_manager, RdfEtlStepPluginManager $step_plugin_manager, RdfEtlStateManager $state_manager, FormBuilderInterface $form_builder, MessengerInterface $messenger) {
    $this->pipelinePluginManager = $pipeline_plugin_manager;
    $this->stepPluginManager = $step_plugin_manager;
    $this->stateManager = $state_manager;
    $this->formBuilder = $form_builder;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function run(string $pipeline) {
    $current_state = $this->initializeActiveState($pipeline);

    // Execute all consecutive steps until we reach one that has output. A step
    // produces response/output in one of the following cases:
    // - It's a step with form.
    // - It's the final step.
    // - It stops the pipeline with an error.
    do {
      if ($new_state = $this->executeStep($current_state)) {
        $this->stateManager->setState($new_state);
        $current_state = $new_state;
      }
    } while (!$this->response);

    return $this->response;
  }

  /**
   * Initializes the state machine from the backend or constructs a new one.
   *
   * @param string $pipeline
   *   The pipeline to be used.
   *
   * @return \Drupal\rdf_etl\RdfEtlState
   *   The current active state.
   */
  protected function initializeActiveState(string $pipeline): RdfEtlState {
    if (!$this->stateManager->isPersisted()) {
      // Initialize to default pipeline.
      $current_state = new RdfEtlState($pipeline, self::FIRST_STEP);
    }
    else {
      $current_state = $this->stateManager->state();
    }
    $this->pipeline = $this->pipelinePluginManager->createInstance($current_state->getPipelineId());
    // Restore the active pipeline from the persistent store.
    $this->pipeline->setActiveStep($current_state->sequence());

    return $current_state;
  }

  /**
   * Executes the current step and progresses the state machine with one step.
   *
   * @param \Drupal\rdf_etl\RdfEtlState $current_state
   *   The current state.
   *
   * @return \Drupal\rdf_etl\RdfEtlState
   *   The next state.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   *   If errors occurred during the form build.
   */
  protected function executeStep(RdfEtlState $current_state): ?RdfEtlState {
    $step_instance = $this->getStepInstance($current_state);
    $data = [
      'state' => $current_state,
      'step' => $step_instance,
    ];

    if ($has_form = $step_instance instanceof RdfEtlStepWithFormInterface) {
      $form_state = new FormState();
      $data = $this->buildForm($step_instance, $form_state, $data);
      // In case of validation errors, or a rebuild (e.g. multi step), bail out.
      if (!$form_state->isExecuted()) {
        // Set the current state.
        $this->stateManager->setState($current_state);
        return NULL;
      }
      $this->redirectForm($form_state);
    }

    $step_instance->execute($data);

    // If this step execution has produced errors, exit here the pipeline
    // execution but show the errors.
    if (!empty($data['error'])) {
      $this->setStepErrorResponse($data);
      $this->stateManager->reset();
      return NULL;
    }

    if ($has_form) {
     // $this->redirectForm($form_state);
    }

    // Advance to next state.
    $data['state'] = $this->getNextState($current_state);

    // The pipeline execution finished with success.
    if ($data['state']->sequence() === self::FINAL_STEP) {
      $this->setSuccessResponse($data);
      $this->stateManager->reset();
      return NULL;
    }

    return $data['state'];
  }

  /**
   * Returns the active process step.
   *
   * @param \Drupal\rdf_etl\RdfEtlState $state
   *   The pipeline state.
   *
   * @return \Drupal\rdf_etl\Plugin\RdfEtlStepInterface
   *   The active process step.
   */
  protected function getStepInstance(RdfEtlState $state): RdfEtlStepInterface {
    $plugin_id = $this->pipeline->getStepPluginId($state->sequence());
    /** @var RdfEtlStepInterface $plugin_instance */
    $plugin_instance = $this->stepPluginManager->createInstance($plugin_id);
    return $plugin_instance;
  }

  /**
   * Builds the form.
   *
   * @param \Drupal\rdf_etl\Plugin\RdfEtlStepInterface $step
   *   The step plugin instance.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $data
   *   The data array.
   *
   * @return array
   *   The data array.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   *   If errors occurred during the form build.
   */
  protected function buildForm(RdfEtlStepInterface $step, FormStateInterface &$form_state, array $data): array {
    $form_state->addBuildInfo('active_step', $step);
    $form_state->addBuildInfo('pipeline', $this->pipeline);
    $form_state->addBuildInfo('data', $data);
    $this->response = $this->formBuilder->buildForm(RdfEtlOrchestratorForm::class, $form_state);
    return $form_state->getBuildInfo()['data'];
  }

  /**
   * Reloads the page if the form needs to rebuild.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function redirectForm(FormStateInterface $form_state): void {
    $form_state->disableRedirect(FALSE);
    if ($redirect = $this->formBuilder->redirectForm($form_state)) {
      $this->response = $redirect;
    }
  }

  /**
   * Builds the state object that points to the next step in the pipeline.
   *
   * @param \Drupal\rdf_etl\RdfEtlState|null $state
   *   The current state.
   *
   * @return \Drupal\rdf_etl\RdfEtlState
   *   The next state.
   */
  protected function getNextState(?RdfEtlState $state): RdfEtlState {
    $this->pipeline->stepDefinitionList()->seek($state->sequence());
    $this->pipeline->stepDefinitionList()->next();
    $sequence = $this->pipeline->stepDefinitionList()->valid() ? $this->pipeline->stepDefinitionList()->key() : static::FINAL_STEP;
    return new RdfEtlState($state->getPipelineId(), $sequence);
  }

  /**
   * Sets the step error response.
   *
   * @param array $data
   *   Step processed data.
   */
  protected function setStepErrorResponse(array $data): void {
    $error = $data['error'];
    $error = is_string($error) || $error instanceof MarkupInterface ? ['#markup' => $error] : $error;
    /** @var \Drupal\rdf_etl\Plugin\RdfEtlStepInterface $step */
    $step = $data['step'];
    $arguments = [
      '%pipeline' => $this->pipeline->getPluginDefinition()['label'],
      '%step' => $step->getPluginDefinition()['label'],
    ];
    $message = $this->t('%pipeline execution stopped with errors in %step step. Please review the following errors:', $arguments);
    $this->messenger->addError($message);
    $this->response = $error + ['#title' => $this->t('Errors executing %pipeline', $arguments)];
  }

  /**
   * Sets the success response.
   *
   * @param array $data
   *   Step processed data.
   */
  protected function setSuccessResponse(array $data): void {
    $arguments = [
      '%pipeline' => $this->pipeline->getPluginDefinition()['label'],
    ];
    $message = $this->t('The %pipeline execution has finished with success.', $arguments);
    $this->messenger->addStatus($message);
    $this->response = ['#title' => $this->t('Successfully executed %pipeline import pipeline', $arguments)];
    // @todo Add a list of executed steps as page content.
  }

  /**
   * {@inheritdoc}
   */
  public function reset(): void {
    $this->stateManager->reset();
  }

}
