<?php

namespace Drupal\pipeline;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\pipeline\Form\PipelineOrchestratorForm;
use Drupal\pipeline\Plugin\PipelinePipelinePluginManager;
use Drupal\pipeline\Plugin\PipelineStepInterface;
use Drupal\pipeline\Plugin\PipelineStepWithFormInterface;

/**
 * The pipeline orchestrator.
 *
 * The orchestrator uses a simple state machine to keep track of progress and
 * coordinates the work. The actual work is performed by plugins.
 */
class PipelineOrchestrator implements PipelineOrchestratorInterface {

  use StringTranslationTrait;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormSubmitterInterface
   */
  protected $formBuilder;

  /**
   * The active pipeline.
   *
   * @var \Drupal\pipeline\Plugin\PipelinePipelineInterface
   */
  protected $pipeline;

  /**
   * The pipeline plugin manager service.
   *
   * @var \Drupal\pipeline\Plugin\PipelinePipelinePluginManager
   */
  protected $pipelinePluginManager;

  /**
   * The response value.
   *
   * @var mixed
   */
  protected $response;

  /**
   * The persistent state of the importer.
   *
   * @var \Drupal\pipeline\PipelineStateManager
   */
  protected $stateManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new PipelineOrchestrator object.
   *
   * @param \Drupal\pipeline\Plugin\PipelinePipelinePluginManager $pipeline_plugin_manager
   *   The pipeline plugin manager service.
   * @param \Drupal\pipeline\PipelineStateManager $state_manager
   *   The persistent state of the importer.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(PipelinePipelinePluginManager $pipeline_plugin_manager, PipelineStateManager $state_manager, FormBuilderInterface $form_builder, MessengerInterface $messenger) {
    $this->pipelinePluginManager = $pipeline_plugin_manager;
    $this->stateManager = $state_manager;
    $this->formBuilder = $form_builder;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function run($pipeline) {
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
   * @return \Drupal\pipeline\PipelineState
   *   The current active state.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  protected function initializeActiveState($pipeline) {
    $this->pipeline = $this->pipelinePluginManager->createInstance($pipeline);

    if (!$this->stateManager->isPersisted()) {
      $current_state = new PipelineState($pipeline, $this->pipeline->current());
    }
    else {
      $current_state = $this->stateManager->getState();
      // Restore the active pipeline step from the persistent store.
      $this->pipeline->setCurrent($current_state->getStep());
    }

    return $current_state;
  }

  /**
   * Executes the current step and progresses the state machine with one step.
   *
   * @param \Drupal\pipeline\PipelineState $current_state
   *   The current state.
   *
   * @return \Drupal\pipeline\PipelineState
   *   The next state.
   *
   * @throws \Exception
   *   If errors occurred during the form build or step execution.
   */
  protected function executeStep(PipelineState $current_state) {
    $step = $this->pipeline->createStepInstance($current_state->getStep());

    $data = [];
    if ($step instanceof PipelineStepWithFormInterface) {
      $form_state = new FormState();
      $data = $this->buildForm($step, $form_state, $data);
      // In case of validation errors, or a rebuild (e.g. multi step), bail out.
      if (!$form_state->isExecuted() || $form_state->getTriggeringElement()['#attributes']['data-drupal-selector'] !== 'edit-next') {
        // Set the current state.
        $this->stateManager->setState($current_state);
        return NULL;
      }
      $this->redirectForm($form_state);
    }

    try {
      $error = $step->prepare($data)->execute($data);
    }
    catch (\Exception $exception) {
      // Catching any exception from the step execution just to reset the
      // pipeline and allowing a future run. Otherwise, on a new pipeline run,
      // the orchestrator will jump again to this step and might get stuck here.
      $this->stateManager->reset();
      // Propagate the exception.
      throw $exception;
    }

    // If this step execution returns errors, exit here the pipeline execution
    // but show the errors.
    if ($error) {
      $this->setStepErrorResponse($error, $step);
      $this->pipeline->onError();
      return NULL;
    }

    // Advance to the next state.
    $this->pipeline->next();

    // The pipeline execution finished with success.
    if (!$this->pipeline->valid()) {
      $this->setSuccessResponse();
      $this->pipeline->onSuccess();
      return NULL;
    }

    return $this->stateManager
      ->setState(new PipelineState($this->pipeline->getPluginId(), $this->pipeline->current()))
      ->getState();
  }

  /**
   * Builds the form.
   *
   * @param \Drupal\pipeline\Plugin\PipelineStepWithFormInterface $step
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
  protected function buildForm(PipelineStepWithFormInterface $step, FormStateInterface &$form_state, array $data) {
    $form_state->addBuildInfo('step', $step->getPluginId());
    $form_state->addBuildInfo('pipeline', $this->pipeline->getPluginId());
    $form_state->addBuildInfo('data', $data);
    $this->response = $this->formBuilder->buildForm(PipelineOrchestratorForm::class, $form_state);
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
   * Sets the step error response.
   *
   * @param array $error
   *   The error message as a render array.
   * @param \Drupal\pipeline\Plugin\PipelineStepInterface $step
   *   The step plugin instance.
   */
  protected function setStepErrorResponse(array $error, PipelineStepInterface $step) {
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
   */
  protected function setSuccessResponse() {
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
