<?php

namespace Drupal\rdf_etl\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rdf_etl\EtlStateManager;
use Drupal\rdf_etl\Plugin\RdfEtlPipelinePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pipeline selector form.
 */
class PipelineSelectForm extends FormBase {

  /**
   * The pipeline plugin manager service.
   *
   * @var \Drupal\rdf_etl\Plugin\RdfEtlPipelinePluginManager
   */
  protected $pipelinePluginManager;

  /**
   * The state manager service.
   *
   * @var \Drupal\rdf_etl\EtlStateManager
   */
  protected $stateManager;

  /**
   * Constructs a new form class.
   *
   * @param \Drupal\rdf_etl\Plugin\RdfEtlPipelinePluginManager $pipeline_plugin_manager
   *   The pipeline plugin manager service.
   * @param \Drupal\rdf_etl\EtlStateManager $state_manager
   *   The state manager service.
   */
  public function __construct(RdfEtlPipelinePluginManager $pipeline_plugin_manager, EtlStateManager $state_manager) {
    $this->pipelinePluginManager = $pipeline_plugin_manager;
    $this->stateManager = $state_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.rdf_etl_pipeline'),
      $container->get('rdf_etl.state_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rdf_etl_select_pipeline';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->stateManager->isPersisted()) {
      $state = $this->stateManager->state();
      return $this->redirect('rdf_etl.execute_pipeline', ['pipeline' => $state->getPipelineId()]);
    }

    $form['inline'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'container-inline',
        ],
      ],
    ];
    $form['inline']['pipeline'] = [
      '#type' => 'select',
      '#title' => $this->t('Data pipeline'),
      '#options' => array_map(function ($pipeline) {
        return $pipeline['label'];
      }, $this->pipelinePluginManager->getDefinitions()),
      '#required' => TRUE,
    ];
    $form['inline']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Execute'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('rdf_etl.execute_pipeline', ['pipeline' => $form_state->getValue('pipeline')]);
  }

}
