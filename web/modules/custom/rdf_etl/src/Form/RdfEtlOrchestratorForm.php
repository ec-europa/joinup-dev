<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\rdf_etl\Plugin\RdfEtlStepInterface;

/**
 * Provides a form class for steps that are exposing forms.
 */
class RdfEtlOrchestratorForm extends FormBase {

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
      '@pipeline' => $form_state->getBuildInfo()['pipeline']->getPluginDefinition()['label'],
      '@step' => $form_state->getBuildInfo()['active_step']->getPluginDefinition()['label'],
    ]);
    $form = $this->buildProgressIndicator($form, $form_state);
    $form['data'] = $this->buildSubForm($form, $form_state);
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
    ];
    return $form;
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
  protected function activeStep(FormStateInterface $form_state): RdfEtlStepInterface {
    if (!isset($form_state->getBuildInfo()['active_step'])) {
      throw new \Exception('No active process step.');
    }
    return $form_state->getBuildInfo()['active_step'];
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
    return ['#tree' => TRUE] + $this->activeStep($form_state)->buildConfigurationForm($subform, $subform_state);
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
    $this->activeStep($form_state)->validateConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->activeStep($form_state)->submitConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));
    $form_state->disableRedirect();
  }

}
