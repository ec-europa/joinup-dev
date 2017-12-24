<?php

namespace Drupal\rdf_etl\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\rdf_etl\EtlOrchestrator;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The active process step.
   *
   * @var \Drupal\rdf_etl\Plugin\EtlProcessStepInterface
   */
  protected $processStep;

  /**
   * Constructs a new PipelineSelectionForm object.
   */
  public function __construct(EtlOrchestrator $rdf_etl_orchestrator) {
    $this->rdfEtlOrchestrator = $rdf_etl_orchestrator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('rdf_etl.orchestrator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'etl_orchestrator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->processStep = $form_state->getBuildInfo()['active_process_step'];
    if (!$this->processStep instanceof PluginFormInterface) {
      return $form;
    }

    $form['data'] = [];
    $subform_state = SubformState::createForSubform($form['data'], $form, $form_state);
    $form['data'] = $this->processStep->buildConfigurationForm($form['data'], $subform_state);
    $form['data']['#tree'] = TRUE;
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->processStep->defaultConfiguration();
    $this->processStep->validateConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $this->processStep->submitConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));
  }

}
