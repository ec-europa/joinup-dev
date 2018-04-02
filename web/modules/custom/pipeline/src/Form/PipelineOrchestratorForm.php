<?php

namespace Drupal\pipeline\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Url;
use Drupal\pipeline\Plugin\PipelinePipelinePluginManager;
use Drupal\pipeline\Plugin\PipelineStepPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form class for steps that are exposing forms.
 */
class PipelineOrchestratorForm extends FormBase {

  /**
   * The pipeline plugin manager service.
   *
   * @var \Drupal\pipeline\Plugin\PipelinePipelinePluginManager
   */
  protected $pipelinePluginManager;

  /**
   * The step plugin manager service.
   *
   * @var \Drupal\pipeline\Plugin\PipelineStepPluginManager
   */
  protected $stepPluginManager;

  /**
   * The current pipeline plugin instance.
   *
   * @var \Drupal\pipeline\Plugin\PipelinePipelineInterface
   */
  protected $currentPipeline;

  /**
   * The current step plugin instance.
   *
   * @var \Drupal\pipeline\Plugin\PipelineStepInterface
   */
  protected $currentStep;

  /**
   * Builds a new form object.
   *
   * @param \Drupal\pipeline\Plugin\PipelinePipelinePluginManager $pipeline_plugin_manager
   *   The pipeline plugin manager service.
   * @param \Drupal\pipeline\Plugin\PipelineStepPluginManager $step_plugin_manager
   *   The step plugin manager service.
   */
  public function __construct(PipelinePipelinePluginManager $pipeline_plugin_manager, PipelineStepPluginManager $step_plugin_manager) {
    $this->pipelinePluginManager = $pipeline_plugin_manager;
    $this->stepPluginManager = $step_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.pipeline_pipeline'),
      $container->get('plugin.manager.pipeline_step')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pipeline_orchestrator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('@pipeline: @step', [
      '@pipeline' => $this->getCurrentPipeline($form_state)->getPluginDefinition()['label'],
      '@step' => $this->getCurrentStep($form_state)->getPluginDefinition()['label'],
    ]);
    $form = $this->buildProgressIndicator($form, $form_state);
    $form['data'] = $this->buildSubForm($form, $form_state);
    $form['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
    ];
    $form['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('pipeline.reset_pipeline'),
    ];

    return $form;
  }

  /**
   * Attaches the plugin form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The sub-form render array.
   *
   * @throws \Exception
   *   When no active step has been passed.
   */
  protected function buildSubForm(array $form, FormStateInterface $form_state) {
    $subform = [];
    $subform_state = SubformState::createForSubform($subform, $form, $form_state);
    return ['#tree' => TRUE] + $this->getCurrentStep($form_state)->buildConfigurationForm($subform, $subform_state);
  }

  /**
   * Attaches the progress indicator to the form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   The form array.
   */
  protected function buildProgressIndicator(array $form, FormStateInterface $form_state) {
    // @todo Implement this.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->getCurrentStep($form_state)->validateConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->getCurrentStep($form_state)->submitConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));
    $form_state->disableRedirect();
  }

  /**
   * Returns the current pipeline given the form state object.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\pipeline\Plugin\PipelinePipelineInterface
   *   The current pipeline.
   *
   * @throws \RuntimeException
   *   If the pipeline plugin ID is not passed in the form build info.
   */
  protected function getCurrentPipeline(FormStateInterface $form_state) {
    if (!isset($this->currentPipeline)) {
      if (!isset($form_state->getBuildInfo()['pipeline'])) {
        throw new \RuntimeException('No active pipeline step.');
      }
      $pipeline_plugin_id = $form_state->getBuildInfo()['pipeline'];
      $this->currentPipeline = $this->pipelinePluginManager->createInstance($pipeline_plugin_id);
    }
    return $this->currentPipeline;
  }

  /**
   * Returns the current step given the form state object.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\pipeline\Plugin\PipelineStepInterface
   *   An instance of the current pipeline step plugin.
   *
   * @throws \RuntimeException
   *   When no active step has been passed.
   */
  protected function getCurrentStep(FormStateInterface $form_state) {
    if (!isset($this->currentStep)) {
      if (!isset($form_state->getBuildInfo()['step'])) {
        throw new \RuntimeException('No active pipeline step.');
      }
      $step_plugin_id = $form_state->getBuildInfo()['step'];
      $this->currentStep = $this->stepPluginManager->createInstance($step_plugin_id);
    }
    return $this->currentStep;
  }

}
