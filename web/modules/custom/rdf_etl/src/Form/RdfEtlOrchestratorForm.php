<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\rdf_etl\Plugin\RdfEtlPipelineInterface;
use Drupal\rdf_etl\Plugin\RdfEtlPipelinePluginManager;
use Drupal\rdf_etl\Plugin\RdfEtlStepInterface;
use Drupal\rdf_etl\Plugin\RdfEtlStepPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form class for steps that are exposing forms.
 */
class RdfEtlOrchestratorForm extends FormBase {

  /**
   * The pipeline plugin manager service.
   *
   * @var \Drupal\rdf_etl\Plugin\RdfEtlPipelinePluginManager
   */
  protected $pipelinePluginManager;

  /**
   * The step plugin manager service.
   *
   * @var \Drupal\rdf_etl\Plugin\RdfEtlStepPluginManager
   */
  protected $stepPluginManager;

  /**
   * The current pipeline plugin instance.
   *
   * @var \Drupal\rdf_etl\Plugin\RdfEtlPipelineInterface
   */
  protected $currentPipeline;

  /**
   * The current step plugin instance.
   *
   * @var \Drupal\rdf_etl\Plugin\RdfEtlStepInterface
   */
  protected $currentStep;

  /**
   * Builds a new form object.
   *
   * @param \Drupal\rdf_etl\Plugin\RdfEtlPipelinePluginManager $pipeline_plugin_manager
   *   The pipeline plugin manager service.
   * @param \Drupal\rdf_etl\Plugin\RdfEtlStepPluginManager $step_plugin_manager
   *   The step plugin manager service.
   */
  public function __construct(RdfEtlPipelinePluginManager $pipeline_plugin_manager, RdfEtlStepPluginManager $step_plugin_manager) {
    $this->pipelinePluginManager = $pipeline_plugin_manager;
    $this->stepPluginManager = $step_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.rdf_etl_pipeline'),
      $container->get('plugin.manager.rdf_etl_step')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'rdf_etl_orchestrator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
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
  protected function buildSubForm(array $form, FormStateInterface $form_state): array {
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
  protected function buildProgressIndicator(array $form, FormStateInterface $form_state): array {
    // @todo Implement this.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $this->getCurrentStep($form_state)->validateConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->getCurrentStep($form_state)->submitConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));
    $form_state->disableRedirect();
  }

  /**
   * Returns the current pipeline given the form state object.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\rdf_etl\Plugin\RdfEtlPipelineInterface
   *   The current pipeline.
   *
   * @throws \RuntimeException
   *   If the pipeline plugin ID is not passed in the form build info.
   */
  protected function getCurrentPipeline(FormStateInterface $form_state): RdfEtlPipelineInterface {
    if (!isset($this->currentPipeline)) {
      if (!isset($form_state->getBuildInfo()['pipeline'])) {
        throw new \RuntimeException('No active process step.');
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
   * @return \Drupal\rdf_etl\Plugin\RdfEtlStepInterface
   *   An instance of the current step plugin.
   *
   * @throws \RuntimeException
   *   When no active step has been passed.
   */
  protected function getCurrentStep(FormStateInterface $form_state): RdfEtlStepInterface {
    if (!isset($this->currentStep)) {
      if (!isset($form_state->getBuildInfo()['active_step'])) {
        throw new \RuntimeException('No active process step.');
      }
      $step_plugin_id = $form_state->getBuildInfo()['active_step'];
      $this->currentStep = $this->stepPluginManager->createInstance($step_plugin_id);
    }
    return $this->currentStep;
  }

}
