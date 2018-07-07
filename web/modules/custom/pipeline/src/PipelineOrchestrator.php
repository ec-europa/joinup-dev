<?php

namespace Drupal\pipeline;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
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
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

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
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(PipelinePipelinePluginManager $pipeline_plugin_manager, PipelineStateManager $state_manager, FormBuilderInterface $form_builder, MessengerInterface $messenger, AccountProxyInterface $current_user, RequestStack $request_stack, RendererInterface $renderer) {
    $this->pipelinePluginManager = $pipeline_plugin_manager;
    $this->stateManager = $state_manager;
    $this->formBuilder = $form_builder;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function run($pipeline_id) {
    $state = $this->getCurrentState($pipeline_id);

    // Execute all consecutive steps until we reach one that has output. A step
    // produces response/output in one of the following cases:
    // - It's a step with form.
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
   *   If the pipeline has successfully advanced to the new step.
   *
   * @throws \Exception
   *   If errors occurred during the form build or step execution.
   *
   * @todo This method needs more love. It should allow a developer to read more
   * clear the code when a step is a combination of the following cases:
   * - Simple step.
   * - Step with form.
   * - Step with response.
   * - Step running in batch process.
   * @todo Group contiguous steps that are running in batch processes so that we
   * are not refreshing the page between such steps.
   */
  protected function executeStep(PipelineStateInterface $state) {
    $step = $this->pipeline->createStepInstance($state->getStepId());

    if ($is_batch = $step instanceof PipelineStepWithBatchInterface) {
      /** @var \Drupal\pipeline\Plugin\PipelineStepWithBatchInterface $step */
      if (($batch_sequence = $state->getBatchCurrentSequence()) === 0) {
        // If the sequence is 0 we're initializing a new batch.
        $state->setBatchTotalEstimatedIterations($step->initBatchProcess());
      }
    }

    // Handle steps with forms.
    if ($step instanceof PipelineStepWithFormInterface && $this->handleFormExecution($step, $state)) {
      return FALSE;
    }

    try {
      $error = NULL;

      // Run the step preparation only at the beginning of the step.
      if (!$is_batch || ($batch_sequence === 0)) {
        $error = $step->prepare();
      }

      if (!$error) {
        // Execute the step and get any error.
        $error = $step->execute();

        // Collect the errors on batch processing because we want to show them
        // at the end of the batch process.
        if ($is_batch && $error) {
          $state->addBatchErrorMessage($error);
        }

        // Optimization: The batch process is completed after running the first
        // batch. In this case, we treat this as a non-batch step.
        if ($is_batch && ($batch_sequence === 0) && $step->batchProcessIsCompleted()) {
          $is_batch = FALSE;
          $step->onBatchProcessCompleted();
          // The error message should be rebuilt using the batch process logic.
          $error = $step->buildBatchProcessErrorMessage();
          $state->resetBatch();
        }
      }
    }
    catch (\Exception $exception) {
      // Catching any exception from the step execution just to reset the
      // pipeline and allow a future run. Otherwise, on a new pipeline run, the
      // orchestrator will jump again to this step and might get stuck here.
      $this->stateManager->reset($this->pipeline->getPluginId());
      // Propagate the exception.
      throw $exception;
    }

    // If this step not implementing batch process and it returns errors, exit
    // here the pipeline execution but show the errors.
    if ($error && !$is_batch) {
      $this->pipeline->onError();
      $this->setErrorResponse($step, $error);
      return FALSE;
    }

    // Advance to the next state only if batch process is completed.
    if ($is_batch && $this->handleBatchProcess($step)) {
      return FALSE;
    }
    // Advance to the next state.
    elseif (!$is_batch) {
      $this->pipeline->next();
    }

    // The pipeline execution finished with success.
    if (!$this->pipeline->valid() && !$is_batch) {
      $this->setSuccessResponse();
      $this->pipeline->onSuccess();
      return FALSE;
    }

    // Update the state object with the new step ID.
    $state->setStepId($this->pipeline->key());
    // And save it to be retrieved by the next step execution.
    $this->stateManager->setState($this->pipeline->getPluginId(), $state);

    if ($step instanceof PipelineStepWithResponseInterface) {
      $response = $step->getResponse();

      // Provide a fall-back page title, if the step hasn't provided one.
      if (is_array($response) && !isset($response['#title'])) {
        $response = [$response] + ['#title' => $step->getPageTitle()];
      }

      $this->response = $response;
    }

    // Let the orchestrator know that we've advanced to the next step.
    return TRUE;
  }

  /**
   * Handles the form execution.
   *
   * @param \Drupal\pipeline\Plugin\PipelineStepWithFormInterface $step
   *   The active pipeline step.
   * @param \Drupal\pipeline\PipelineStateInterface $state
   *   The state.
   *
   * @return bool
   *   Either the form data, or FALSE to stop processing.
   */
  protected function handleFormExecution(PipelineStepWithFormInterface $step, PipelineStateInterface $state) {
    if ($step instanceof PipelineStepWithBatchInterface) {
      if ($state->getBatchCurrentSequence() > 0) {
        // If a batch is running, skip form rendering.
        return FALSE;
      }
    }

    $form_state = new FormState();
    $this->buildForm($step, $form_state);

    // Add data extracted from the form submit to the persistent data store.
    if ($form_data = $form_state->get('pipeline_data')) {
      $data = $form_data + $this->pipeline->getCurrentState()->getData();;
      $this->pipeline->setCurrentState($state->setData($data));
    }

    // In case of validation errors, or a rebuild (e.g. multi step), bail out.
    if (!$form_state->isExecuted() || $form_state->getTriggeringElement()['#attributes']['data-drupal-selector'] !== 'edit-next') {
      $this->pipeline->saveCurrentState();
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
   * Sets the step error response.
   *
   * @param \Drupal\pipeline\Plugin\PipelineStepInterface $step
   *   The step plugin instance.
   * @param array $error
   *   The error message as a render array.
   */
  protected function setErrorResponse(PipelineStepInterface $step, array $error) {
    $this->messenger->addError($this->getErrorStatusMessage($step));
    $this->response = $error + ['#title' => $this->getErrorPageTitle()];
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
   * Handles progressing the batch process.
   *
   * @param \Drupal\pipeline\Plugin\PipelineStepWithBatchInterface $step
   *   Current pipeline step.
   *
   * @return bool
   *   Signals to the caller tha he should proceed with the response output.
   */
  protected function handleBatchProcess(PipelineStepWithBatchInterface $step) {
    $current = $this->pipeline->getCurrentState()->getBatchCurrentSequence() + 1;
    // As the total iterations is an estimation, we adjust the value to cover
    // the case when there are more iterations than estimated.
    $total = max($this->pipeline->getCurrentState()->getBatchTotalEstimatedIterations(), $current);
    $percentage = (int) (100 * $current / $total);
    $step_label = $step->getPluginDefinition()['label'];
    $strings = [
      'label' => $this->pipeline->getCurrentState()->getBatchCurrentSequence() === 0 ? $this->t("Starting %step", ['%step' => $step_label]) : $this->t('Running step %step', ['%step' => $step_label]),
      'page_title' => $step->getPageTitle(),
      'error_status_message' => $this->getErrorStatusMessage($step),
      'uri' => $this->requestStack->getCurrentRequest()->getPathInfo(),
      'message' => $this->t('Iteration %current of %total', ['%current' => $current, '%total' => $total]),
    ];

    // The current step finished its batch process.
    if ($completed = $step->batchProcessIsCompleted()) {
      // Give steps a chance to run their own code on batch completion.
      $step->onBatchProcessCompleted();

      // Check for errors collected during batch process and aggregate them.
      $render_array = $step->buildBatchProcessErrorMessage();
      if ($is_error = (bool) $render_array) {
        $status = FALSE;
        $strings['error'] = $this->renderer->renderPlain($render_array);
        $strings['uri'] = NULL;
      }
      else {
        $status = TRUE;
      }

      // Feed the progress bar with the last sequence from the batch.
      $this->response = $this->batchResponse($percentage, $strings, $status);

      if (!$is_error) {
        // Resets the batch sandbox and internals.
        $this->pipeline->getCurrentState()->resetBatch();

        // We're done with this step. Advance the pipeline.
        $this->pipeline->next();

        // Check if the pipeline is not completed too.
        if (!$this->pipeline->valid()) {
          // Overwrite the response.
          $success_message = $this->pipeline->getSuccessMessage();
          $strings['message'] = $this->renderer->renderPlain($success_message);
          $strings['uri'] = NULL;
          $this->response = $this->batchResponse($percentage, $strings, $status);
          $this->pipeline->onSuccess();
          return TRUE;
        }

        return FALSE;
      }
      else {
        // Reset the pipeline, on error.
        $this->pipeline->onError();
        return TRUE;
      }
    }

    // The current step has more work to do, so reload the page.
    $this->response = $this->batchResponse($percentage, $strings, TRUE);
    $this->pipeline->getCurrentState()->advanceToNextBatch();

    return FALSE;
  }

  /**
   * Renders a batch progress screen and subsequent Json responses.
   *
   * @param int $percentage
   *   The completion percentage of this batch.
   * @param array $strings
   *   A list of translated strings to be used in the UI for this batch.
   * @param bool $status
   *   The batch process status. FALSE on error, TRUE otherwise.
   *
   * @return array|\Symfony\Component\HttpFoundation\JsonResponse
   *   The render array of the batch process.
   */
  protected function batchResponse($percentage, array $strings, $status = TRUE) {
    // This is a subsequent batch, we only feed the progress bar.
    if ($this->isJsonRequest()) {
      return new JsonResponse([
        'status' => (int) $status,
        'percentage' => $percentage,
        'message' => $strings['message'],
        'label' => $strings['label'],
        'data' => isset($strings['error']) ? $strings['error'] : NULL,
      ]);
    }

    // This condition is reached only by non-Javascript browsers.
    if (!$status) {
      $this->messenger->addError($strings['error_status_message']);
      return [
        '#title' => $this->getErrorPageTitle(),
        [
          '#markup' => $strings['error'],
        ],
      ];
    }

    $build = [
      '#title' => $strings['page_title'],
      [
        '#theme' => 'progress_bar',
        '#percent' => $percentage,
        '#message' => [
          '#markup' => $strings['message'],
        ],
        '#label' => $strings['label'],
        '#attached' => [
          // Code and settings for clients where JavaScript is enabled.
          'drupalSettings' => [
            'batch' => [
              'errorPageTitle' => $this->getErrorPageTitle(),
              'errorMessage' => $strings['error_status_message'],
              'initLabel' => $strings['label'],
              'initMessage' => $strings['message'],
              'percentage' => $percentage,
              'uri' => $strings['uri'],
            ],
          ],
          'library' => [
            'pipeline/batch',
          ],
        ],
      ],
    ];

    // Redirect through a 'Refresh' meta tag for non-Javascript clients.
    if ($strings['uri']) {
      $build['#attached']['html_head'] = [
        [
          [
            '#tag' => 'meta',
            '#noscript' => TRUE,
            '#attributes' => [
              'http-equiv' => 'Refresh',
              'content' => '0; URL=' . $strings['uri'],
            ],
          ],
          'pipeline_batch_progress_meta_refresh',
        ],
      ];
    }

    return $build;
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
