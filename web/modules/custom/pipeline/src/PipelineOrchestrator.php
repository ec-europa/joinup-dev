<?php

namespace Drupal\pipeline;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\pipeline\Exception\PipelineStepExecutionLogicException;
use Drupal\pipeline\Exception\PipelineStepPrepareLogicException;
use Drupal\pipeline\Form\PipelineOrchestratorForm;
use Drupal\pipeline\Plugin\PipelinePipelinePluginManager;
use Drupal\pipeline\Plugin\PipelineStepWithBatchInterface;
use Drupal\pipeline\Plugin\PipelineStepInterface;
use Drupal\pipeline\Plugin\PipelineStepWithFormInterface;
use Drupal\pipeline\Plugin\PipelineStepWithResponseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

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
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(PipelinePipelinePluginManager $pipeline_plugin_manager, PipelineStateManager $state_manager, FormBuilderInterface $form_builder, MessengerInterface $messenger, AccountProxyInterface $current_user, RequestStack $request_stack) {
    $this->pipelinePluginManager = $pipeline_plugin_manager;
    $this->stateManager = $state_manager;
    $this->formBuilder = $form_builder;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function run($pipeline_id) {
    $state = $this->getCurrentState($pipeline_id);

    // Execute all consecutive steps until we reach one that has output. A step
    // produces response/output in one of the following cases:
    // - It's a step with form.
    // - It's a step with response (could be a page content or a redirect).
    // - It's a Json response of a subsequent batch for steps running in batch
    //   process.
    // - It's the final step.
    // - It stops the pipeline with an error.
    while (!$this->response) {
      if ($this->executeStep($state)) {
        $this->stateManager->saveState($pipeline_id, $state);
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
   * @return \Drupal\pipeline\PipelineStateInterface
   *   The current pipeline state object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  protected function getCurrentState($pipeline_id) {
    $this->pipeline = $this->pipelinePluginManager->createInstance($pipeline_id);

    // Starting the pipeline from the beginning.
    if (!$state = $this->stateManager->getState($pipeline_id)) {
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
      $state = (new PipelineState())->setStepId($this->pipeline->key());
    }

    // Restore the active pipeline state from the persistent store.
    $this->pipeline->setCurrentState($state);

    return $state;
  }

  /**
   * Executes the current step and progresses the state machine with one step.
   *
   * @param \Drupal\pipeline\PipelineStateInterface $state
   *   The current pipeline state.
   *
   * @return bool
   *   Instructs the caller whether to save the state or not.
   *
   * @throws \Exception
   *   If errors occurred during the form build or step execution.
   *
   * @todo Group contiguous steps that are running in batch processes so that we
   *   are not refreshing the page between such steps.
   */
  protected function executeStep(PipelineStateInterface $state) {
    $step = $this->pipeline->createStepInstance($state->getStepId());

    // Handle steps with forms.
    if ($step instanceof PipelineStepWithFormInterface && $this->showForm($step, $state)) {
      // The form render array is set as response. Instruct the caller to save
      // any state changes.
      return TRUE;
    }

    if ($is_batch = $step instanceof PipelineStepWithBatchInterface) {
      /** @var \Drupal\pipeline\Plugin\PipelineStepWithBatchInterface $step */
      if ($state->batchProcessIsStarted() && $step->batchProcessIsCompleted()) {
        // We landed here after all batches were processed in the last request.
        if ($state->hasBatchProcessErrors()) {
          // The last batch from this step has just finished and there are
          // errors. Show the errors and instruct the caller that he should not
          // save a new/empty state.
          $this->onError($step, $step->buildBatchProcessErrorMessage());
          return FALSE;
        }

        // Give step plugin a chance to run its own code on batch completion.
        $step->onBatchProcessCompleted();
        // Reset the batch process sandbox to be prepared for the next step.
        $state->resetBatchProcess();

        // We're done with this step. Advance to the next step but find out if
        // the state object should be persisted or not.
        return $this->advanceToNextStep($step, $state);
      }
    }

    try {
      // Run the step preparation only at the beginning of the step.
      if (!$is_batch || !$state->batchProcessIsStarted()) {
        $step->prepare();
      }
    }
    // Logical exception in the prepare phase. We handle the error.
    catch (PipelineStepPrepareLogicException $exception) {
      // End the pipeline with error and instruct the caller not to save a
      // new/empty state.
      $this->onError($step, $exception->getError());
      return FALSE;
    }

    if ($is_batch) {
      if (!$state->batchProcessIsStarted()) {
        // Initialize a new batch process.
        $state->setBatchProcessIterations($step->initBatchProcess());
        // Show the page with the progress bar for the first time in this step.
        $this->batchResponse($step, $state);
        // Mark the batch process as started.
        $state->advanceToNextBatch();
        // Instruct the caller to save any state changes.
        return TRUE;
      }
    }

    try {
      // Actually execute the step.
      $step->execute();
    }
    // Logical exception in the execution phase. Let's handle the error.
    catch (PipelineStepExecutionLogicException $exception) {
      if (!$is_batch) {
        $this->onError($step, $exception->getError());
        // The state was reset. The caller shouldn't save a new, empty state.
        return FALSE;
      }
      // On batch processing only collect the messages to show them at the end.
      $state->addBatchProcessErrorMessage($exception->getError());
    }
    // Any other exception.
    catch (\Exception $exception) {
      // Catching any other exception from the step execution just to reset the
      // pipeline and allow a future run. Otherwise, on a new pipeline run, the
      // orchestrator will jump again to this step and might get stuck here.
      $this->stateManager->reset($this->pipeline->getPluginId());
      // Propagate the exception.
      throw $exception;
    }

    if ($is_batch) {
      // We've just executed a specific batch, update the progress bar and
      // instruct the caller to save any state changes.
      $this->batchResponse($step, $state);
      $state->advanceToNextBatch();
      return TRUE;
    }

    // We landed here only after a non-batch steps. We're done with this step,
    // so advance to the next step and find out if the state object should be
    // persisted or not.
    return $this->advanceToNextStep($step, $state);
  }

  /**
   * Advances the pipeline to the next step.
   *
   * @param \Drupal\pipeline\Plugin\PipelineStepInterface $step
   *   The current step.
   * @param \Drupal\pipeline\PipelineStateInterface $state
   *   The pipeline state object.
   *
   * @return bool
   *   Instructs the caller if the state object should be persisted.
   */
  protected function advanceToNextStep(PipelineStepInterface $step, PipelineStateInterface $state) {
    // Set the response for steps that are producing response.
    if ($step instanceof PipelineStepWithResponseInterface) {
      $response = $step->getResponse();
      // Provide a fallback page title, if the step hasn't provided one.
      if (is_array($response) && !isset($response['#title'])) {
        $response = [$response] + ['#title' => $step->getPageTitle()];
      }
      $this->response = $response;
    }

    // Actually, advance to the next step.
    $this->pipeline->next();

    // The pipeline execution finished with success.
    if (!$this->pipeline->valid()) {
      $this->onSuccess();
      // The state was reset. The caller shouldn't save a new/empty state.
      return FALSE;
    }

    // Update the state object with the new step ID and instruct the caller to
    // save the any changes to the state.
    $state->setStepId($this->pipeline->key());
    return TRUE;
  }

  /**
   * Renders a batch progress bar screen or subsequent Json responses.
   *
   * @param \Drupal\pipeline\Plugin\PipelineStepWithBatchInterface $step
   *   The current step.
   * @param \Drupal\pipeline\PipelineStateInterface $state
   *   The pipeline state object.
   */
  protected function batchResponse(PipelineStepWithBatchInterface $step, PipelineStateInterface $state) {
    $arguments = ['%step' => $step->getPluginDefinition()['label']];

    if (!$state->batchProcessIsStarted()) {
      $percentage = 0;
      $label = $this->t('Starting %step', $arguments);
      $message = $this->t('Preparing to run step %step', $arguments);
    }
    else {
      $current = $state->getBatchProcessSequence() + 1;
      $total = $state->getBatchProcessIterations();
      $percentage = (int) (100 * $current / $total);
      $label = $this->t('Running step %step', $arguments);
      $message = $this->t('Iteration %current of %total', ['%current' => $current, '%total' => $total]);
    }
    $page_title = $step->getPageTitle();
    $error_status_message = $this->getErrorStatusMessage($step);
    $uri = $this->requestStack->getCurrentRequest()->getPathInfo();

    // Respond with Json when Javascript is enabled.
    if ($this->isJsonRequest()) {
      $this->response = new JsonResponse([
        'status' => 1,
        'percentage' => $percentage,
        'message' => $message,
        'label' => $label,
      ]);
      return;
    }

    $this->response = [
      '#title' => $page_title,
      [
        '#theme' => 'progress_bar',
        '#percent' => $percentage,
        '#message' => [
          '#markup' => $message,
        ],
        '#label' => $label,
        '#attached' => [
          // Redirect through a 'Refresh' meta tag for non-Javascript clients.
          'html_head' => [
            [
              [
                '#tag' => 'meta',
                '#noscript' => TRUE,
                '#attributes' => [
                  'http-equiv' => 'Refresh',
                  'content' => "0; URL=$uri",
                ],
              ],
              'pipeline_batch_progress_meta_refresh',
            ],
          ],
          // Code and settings for clients where JavaScript is enabled.
          'drupalSettings' => [
            'batch' => [
              'errorPageTitle' => $this->getErrorPageTitle(),
              'errorMessage' => $error_status_message,
              'initLabel' => $label,
              'initMessage' => $message,
              'percentage' => $percentage,
              'uri' => $uri,
            ],
          ],
          'library' => [
            'pipeline/batch',
          ],
        ],
      ],
    ];
  }

  /**
   * Executes the and instructs the caller that he should render the form.
   *
   * @param \Drupal\pipeline\Plugin\PipelineStepWithFormInterface $step
   *   The active pipeline step.
   * @param \Drupal\pipeline\PipelineStateInterface $state
   *   The state.
   *
   * @return bool
   *   Return TRUE if the form is about to be rendered.
   */
  protected function showForm(PipelineStepWithFormInterface $step, PipelineStateInterface $state) {
    if ($step instanceof PipelineStepWithBatchInterface) {
      if ($state->batchProcessIsStarted()) {
        // If a batch is running, skip the form rendering.
        return FALSE;
      }
    }

    $form_state = new FormState();
    $this->buildForm($step, $form_state);

    // Add data extracted from the form submit to the persistent data store.
    if ($form_data = $form_state->get('pipeline_data')) {
      $data = $form_data + $this->pipeline->getCurrentState()->getData();;
      $state->setData($data);
    }

    // In case of validation errors, or a rebuild (e.g. multi step), bail out.
    if (!$form_state->isExecuted() || $form_state->getTriggeringElement()['#attributes']['data-drupal-selector'] !== 'edit-next') {
      // The response was set as a form render array. Let's show the form.
      return TRUE;
    }

    $this->redirectForm($form_state);
    return FALSE;
  }

  /**
   * Builds the form.
   *
   * @param \Drupal\pipeline\Plugin\PipelineStepWithFormInterface $step
   *   The step plugin instance.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   *   If errors occurred during the form build.
   */
  protected function buildForm(PipelineStepWithFormInterface $step, FormStateInterface &$form_state) {
    $form_state->set('pipeline_step', $step);
    $form_state->set('pipeline_pipeline', $this->pipeline);
    $this->response = $this->formBuilder->buildForm(PipelineOrchestratorForm::class, $form_state);
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
   * Acts when the pipeline exits with error.
   *
   * @param \Drupal\pipeline\Plugin\PipelineStepInterface $step
   *   The step plugin instance.
   * @param array $error
   *   The error message as a render array.
   */
  protected function onError(PipelineStepInterface $step, array $error) {
    $this->pipeline->onError();
    $this->messenger->addError($this->getErrorStatusMessage($step));
    $this->response = $error + ['#title' => $this->getErrorPageTitle()];
  }

  /**
   * Acts on a successful pipeline finish.
   */
  protected function onSuccess() {
    $this->pipeline->onSuccess();

    $arguments = [
      '%pipeline' => $this->pipeline->getPluginDefinition()['label'],
    ];
    $message = $this->t('The %pipeline execution has finished with success.', $arguments);
    $this->messenger->addStatus($message);

    $success_message = $this->pipeline->getSuccessMessage();
    $this->response = (array) $success_message + ['#title' => $this->t('Successfully executed %pipeline import pipeline', $arguments)];
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
   * Checks if the current request is a Json request.
   *
   * @return bool
   *   If the current request is a batch process subsequent Json request.
   */
  protected function isJsonRequest() {
    return $this->requestStack->getCurrentRequest()->getRequestFormat() === 'json';
  }

  /**
   * Returns the status message when the pipeline exists with error.
   *
   * @param \Drupal\pipeline\Plugin\PipelineStepInterface $step
   *   The pipeline step plugin instance.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The error message.
   */
  protected function getErrorStatusMessage(PipelineStepInterface $step) {
    return $this->t('%pipeline execution stopped with errors in %step step. Please review the following errors:', [
      '%pipeline' => $this->pipeline->getPluginDefinition()['label'],
      '%step' => $step->getPluginDefinition()['label'],
    ]);
  }

  /**
   * Returns the page title on error.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The error message.
   */
  protected function getErrorPageTitle() {
    return $this->t('Errors executing @pipeline', ['@pipeline' => $this->pipeline->getPluginDefinition()['label']]);
  }

}
