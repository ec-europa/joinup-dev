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
    $pipeline = $this->getCurrentPipeline($form_state);
    $form['#title'] = $this->t('@pipeline: @step', [
      '@pipeline' => $pipeline->getPluginDefinition()['label'],
      '@step' => $this->getCurrentStep($form_state)->getPluginDefinition()['label'],
    ]);
    $form = $this->buildProgressIndicator($form, $form_state);
    $form['data'] = $this->buildSubForm($form, $form_state);
    $form['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
    ];
    $pipeline_id = $pipeline->getPluginId();
    $form['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('pipeline.reset_pipeline', ['pipeline' => $pipeline_id]),
      '#access' => $this->currentUser()->hasPermission("reset $pipeline_id pipeline"),
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
   */
  protected function getCurrentPipeline(FormStateInterface $form_state) {
    return $form_state->get('pipeline_pipeline');
  }

  /**
   * Returns the current step given the form state object.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\pipeline\Plugin\PipelineStepWithFormInterface
   *   An instance of the current pipeline step plugin.
   */
  protected function getCurrentStep(FormStateInterface $form_state) {
    return $form_state->get('pipeline_step');
  }

}
