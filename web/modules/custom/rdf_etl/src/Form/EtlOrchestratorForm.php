<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\rdf_etl\Plugin\RdfEtlStepInterface;

/**
 * Class EtlOrchestratorForm.
 */
class EtlOrchestratorForm extends FormBase {

  /**
   * Drupal\rdf_etl\EtlOrchestrator definition.
   *
   * @var \Drupal\rdf_etl\EtlOrchestrator
   */
  protected $rdfEtlOrchestrator;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'etl_orchestrator_form';
  }

  /**
   * Builds an instance of the ProcessStep plugin.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\rdf_etl\Plugin\RdfEtlStepInterface
   *   An instance of the current step plugin.
   *
   * @throws \Exception
   *   When no active step has been passed.
   */
  protected function activeProcessStep(FormStateInterface $form_state): RdfEtlStepInterface {
    if (!isset($form_state->getBuildInfo()['active_step'])) {
      throw new \Exception('No active process step.');
    }
    return $form_state->getBuildInfo()['active_step'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = $this->buildProgressIndicator($form, $form_state);
    if (!$this->activeProcessStep($form_state) instanceof PluginFormInterface) {
      return $this->selfSubmittingForm($form, $form_state);
    }
    $form['#title'] = $this->t('@pipeline: @step', [
      '@pipeline' => $form_state->getBuildInfo()['pipeline']->getPluginDefinition()['label'],
      '@step' => $form_state->getBuildInfo()['active_step']->getPluginDefinition()['label'],
    ]);
    return $this->buildSubForm($form, $form_state);
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
   *   The form array.
   *
   * @throws \Exception
   *   When no active step has been passed.
   */
  protected function buildSubForm(array $form, FormStateInterface $form_state): array {
    $form['data'] = [];
    $subform_state = SubformState::createForSubform($form['data'], $form, $form_state);
    $form['data'] = $this->activeProcessStep($form_state)->buildConfigurationForm($form['data'], $subform_state);
    $form['data']['#tree'] = TRUE;
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * Attaches the self-submit button to the form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   The form array.
   */
  protected function selfSubmittingForm(array $form, FormStateInterface $form_state): array {
    // @todo Implement this.
    return $form;
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
    $this->activeProcessStep($form_state)->defaultConfiguration();
    $this->activeProcessStep($form_state)->validateConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->activeProcessStep($form_state)->submitConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));
    $form_state->disableRedirect();
  }

  /**
   * Make sure we don't have dependencies when we're serialised out.
   *
   * @todo Can be removed?
   *
   * @return array
   *   Empty array.
   */
  public function __sleep() {
    return [];
  }

}
