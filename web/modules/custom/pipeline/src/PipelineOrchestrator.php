<?php

namespace Drupal\pipeline;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\pipeline\Form\PipelineOrchestratorForm;
use Drupal\pipeline\Plugin\PipelinePipelinePluginManager;
use Drupal\pipeline\Plugin\PipelineStepBatchInterface;
use Drupal\pipeline\Plugin\PipelineStepWithFormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new pipeline orchestrator object.
   *
   * @param \Drupal\pipeline\Plugin\PipelinePipelinePluginManager $pipeline_plugin_manager
   *   The pipeline plugin manager service.
   * @param \Drupal\pipeline\PipelineStateManager $state_manager
   *   The persistent state of the importer.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(PipelinePipelinePluginManager $pipeline_plugin_manager, PipelineStateManager $state_manager, FormBuilderInterface $form_builder, MessengerInterface $messenger, AccountProxyInterface $current_user) {
    $this->pipelinePluginManager = $pipeline_plugin_manager;
    $this->stateManager = $state_manager;
    $this->formBuilder = $form_builder;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function run($pipeline_id) {
    $current_step_id = $this->getCurrentStep($pipeline_id);

    // Execute all consecutive steps until we reach one that has output. A step
    // produces response/output in one of the following cases:
    // - It's a step with form.
    // - It's the final step.
    // - It stops the pipeline with an error.
    while (!$this->response) {
      if ($new_step_id = $this->executeStep($current_step_id)) {
        $this->stateManager->setState($pipeline_id, $new_step_id);
        $current_step_id = $new_step_id;
      }
    }

    return $this->response;
  }

  /**
   * Initializes the state machine from the backend or start a new pipeline.
   *
   * @param string $pipeline_id
   *   The pipeline to be used.
   *
   * @return string
   *   The current step plugin ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  protected function getCurrentStep($pipeline_id) {
    $this->pipeline = $this->pipelinePluginManager->createInstance($pipeline_id);

    // Resuming from a previous persisted state.
    if ($current_step_id = $this->stateManager->getState($pipeline_id)) {
      // Restore the active pipeline step from the persistent store.
      $this->pipeline->setCurrent($current_step_id);
    }
    // Starting the pipeline from the beginning.
    else {
      $current_step_id = $this->pipeline->key();

      // Run the pipeline preparation.
      $error = $this->pipeline->prepare();

      // If this pipeline preparation returns errors, exit here the pipeline
      // execution but show the errors as error messages.
      if ($error) {
        $arguments = [
          '%pipeline' => $this->pipeline->getPluginDefinition()['label'],
          '@reason' => $error,
        ];
        $message = $this->t('%pipeline failed to start. Reason: @reason', $arguments);
        $this->messenger->addError($message);

        if ($this->currentUser->hasPermission("access pipeline selector")) {
          $url = Url::fromRoute('pipeline.pipeline_select');
        }
        else {
          $url = Url::fromUri('internal:/<front>');
        }

        $this->response = new RedirectResponse($url->setAbsolute()->toString());
      }
    }

    return $current_step_id;
  }

  /**
   * Executes the current step and progresses the state machine with one step.
   *
   * @param string $current_step_id
   *   The current pipeline step plugin ID.
   *
   * @return string
   *   The next step plugin ID.
   *
   * @throws \Exception
   *   If errors occurred during the form build or step execution.
   */
  protected function executeStep($current_step_id) {
    $step = $this->pipeline->createStepInstance($current_step_id);
    $data = [];
    if ($step instanceof PipelineStepBatchInterface) {
      $this->initialiseBatch($step);
    }

    if ($step instanceof PipelineStepWithFormInterface) {
      $data = $this->handleFormExecution($step);
      if ($data === FALSE) {
        return NULL;
      }
    }

    try {
      $error = $step->prepare($data);
      if (!$error) {
        $error = $step->execute($data);
      }
    }
    catch (\Exception $exception) {
      // Catching any exception from the step execution just to reset the
      // pipeline and allowing a future run. Otherwise, on a new pipeline run,
      // the orchestrator will jump again to this step and might get stuck here.
      $this->stateManager->reset($this->pipeline->getPluginId());
      // Propagate the exception.
      throw $exception;
    }

    // If this step execution returns errors, exit here the pipeline execution
    // but show the errors.
    if ($error) {
      $arguments = [
        '%pipeline' => $this->pipeline->getPluginDefinition()['label'],
        '%step' => $step->getPluginDefinition()['label'],
      ];
      $message = $this->t('%pipeline execution stopped with errors in %step step. Please review the following errors:', $arguments);
      $this->setErrorResponse($error, $message);
      $this->pipeline->onError();
      return NULL;
    }

    // Advance to the next state.
    if ($step instanceof PipelineStepBatchInterface) {
      $this->handleBatchProcess($step);
    }
    else {
      $this->pipeline->next();
    }

    // The pipeline execution finished with success.
    if (!$this->pipeline->valid()) {
      $this->setSuccessResponse();
      $this->pipeline->onSuccess();
      return NULL;
    }

    return $this->pipeline->key();
  }

  /**
   * Handles the form execution.
   *
   * @param \Drupal\pipeline\Plugin\PipelineStepWithFormInterface $step
   *   The active pipeline step.
   *
   * @return array|bool
   *   Either the form data, or FALSE to stop processing.
   */
  protected function handleFormExecution(PipelineStepWithFormInterface $step) {
    if ($step instanceof PipelineStepBatchInterface) {
      // If a batch is running, skip form rendering.
      if (!$step->getProgress()->needsInitialisation()) {
        // Bail out, but keep processing.
        return [];
      }
    }
    $form_state = new FormState();
    $data = $this->buildForm($step, $form_state, []);
    // In case of validation errors, or a rebuild (e.g. multi step), bail out.
    if (!$form_state->isExecuted() || $form_state->getTriggeringElement()['#attributes']['data-drupal-selector'] !== 'edit-next') {
      // Set the current state.
      $this->stateManager->setState($this->pipeline->getPluginId(), $step->getPluginId());
      return FALSE;
    }
    $this->redirectForm($form_state);
    return $data;
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
   * @param \Drupal\Component\Render\MarkupInterface $message
   *   The message to be shown on the top of the page.
   */
  protected function setErrorResponse(array $error, MarkupInterface $message) {
    $this->messenger->addError($message);
    $this->response = $error + ['#title' => $this->t('Errors executing %pipeline', ['%pipeline' => $this->pipeline->getPluginDefinition()['label']])];
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
  public function reset($pipeline) {
    $plugin = $this->pipelinePluginManager->createInstance($pipeline);
    // Ask the plugin to act on reset.
    $plugin->reset();
  }

  /**
   * Renders a batch progress screen.
   *
   * @param \Drupal\pipeline\Plugin\PipelineStepBatchInterface $step
   *   The active pipeline step.
   *
   * @return array
   *   The render array of the batch process.
   */
  protected function batchResponse(PipelineStepBatchInterface $step) {
    $arguments = [
      '%pipeline' => $this->pipeline->getPluginDefinition()['label'],
      '%step' => $step->getPluginDefinition()['label'],
    ];
    $total_count = $step->getProgress()->getTotalBatchIterations();
    $current_count = $step->getProgress()->getBatchIteration();
    $message = $this->t('We got some work to do for the "%step" step. Please bear with us...', $arguments);
    return [
      '#theme' => 'progress_bar',
      '#percent' => $total_count ? (int) (100 * $current_count / $total_count) : 100,
      '#message' => [
        '#markup' => $message,
      ],
      '#label' => $this->t('%pipeline - %step', $arguments),
      '#attached' => [
        'html_head' => [
          [
            [
              // Redirect through a 'Refresh' meta tag.
              '#tag' => 'meta',
              '#attributes' => [
                'http-equiv' => 'Refresh',
                'content' => '0; URL=' . \Drupal::service('path.current')->getPath(),
              ],
            ],
            'batch_progress_meta_refresh',
          ],
        ],
      ],
    ];
  }

  /**
   * Handle progressing the batch process.
   *
   * @param \Drupal\pipeline\Plugin\PipelineStepBatchInterface $step
   *   Current pipeline step.
   */
  protected function handleBatchProcess(PipelineStepBatchInterface $step): void {
    // The current step finished its batch operation.
    if ($step->getProgress()->getCompleted()) {
      $this->stateManager->resetBatchProgress($this->pipeline->getPluginId());
      $this->pipeline->next();
    }
    // The current step has more work to, so reload the page.
    else {
      $this->stateManager->setBatchProgress($this->pipeline->getPluginId(), $step->getProgress());
      $this->response = $this->batchResponse($step);
    }
  }

  /**
   * Set the batch progress on the batch step.
   *
   * @param \Drupal\pipeline\Plugin\PipelineStepBatchInterface $step
   *   Current pipeline step.
   */
  protected function initialiseBatch(PipelineStepBatchInterface $step): void {
    $batch_progress = $this->stateManager->getBatchProgress($this->pipeline->getPluginId());
    $step->setProgress($batch_progress);
  }

}
