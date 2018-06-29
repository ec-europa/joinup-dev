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
    $state = $this->getCurrentState($pipeline_id);

    // Execute all consecutive steps until we reach one that has output. A step
    // produces response/output in one of the following cases:
    // - It's a step with form.
    // - It's the final step.
    // - It stops the pipeline with an error.
    while (!$this->response) {
      if ($this->executeStep($state)) {
        $this->stateManager->setState($pipeline_id, $state);
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
   */
  protected function executeStep(PipelineStateInterface $state) {
    $step = $this->pipeline->createStepInstance($state->getStepId());

    if ($step instanceof PipelineStepWithFormInterface) {
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
        return FALSE;
      }
      $this->redirectForm($form_state);
    }

    try {
      $error = $step->prepare();
      if (!$error) {
        $error = $step->execute();
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
      return FALSE;
    }

    // Refresh the state object because the last step might have altered the
    // content of the persistent data store.
    $state = $this->pipeline->getCurrentState();

    // Advance to the next state.
    $this->pipeline->next();

    // The pipeline execution finished with success.
    if (!$this->pipeline->valid()) {
      $success_message = $this->pipeline->onSuccess();
      $this->setSuccessResponse($success_message);
      return FALSE;
    }

    // Update the state object with the new step ID.
    $state->setStepId($this->pipeline->key());
    // And save it to be retrieved by the next step execution.
    $this->stateManager->setState($this->pipeline->getPluginId(), $state);

    // Let the orchestrator know that we've advanced to the next step.
    return TRUE;
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
   *
   * @param array|null $success_message
   *   (optional) An optional success message as a render array.
   */
  protected function setSuccessResponse(array $success_message = NULL) {
    $arguments = [
      '%pipeline' => $this->pipeline->getPluginDefinition()['label'],
    ];
    $message = $this->t('The %pipeline execution has finished with success.', $arguments);
    $this->messenger->addStatus($message);

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

}
