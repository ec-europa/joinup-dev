<?php

namespace Drupal\pipeline\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pipeline\PipelineStateManager;
use Drupal\pipeline\Plugin\PipelinePipelinePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pipeline selector form.
 */
class PipelineSelectForm extends FormBase {

  /**
   * The pipeline plugin manager service.
   *
   * @var \Drupal\pipeline\Plugin\PipelinePipelinePluginManager
   */
  protected $pipelinePluginManager;

  /**
   * The state manager service.
   *
   * @var \Drupal\pipeline\PipelineStateManager
   */
  protected $stateManager;

  /**
   * Constructs a new form class.
   *
   * @param \Drupal\pipeline\Plugin\PipelinePipelinePluginManager $pipeline_plugin_manager
   *   The pipeline plugin manager service.
   * @param \Drupal\pipeline\PipelineStateManager $state_manager
   *   The state manager service.
   */
  public function __construct(PipelinePipelinePluginManager $pipeline_plugin_manager, PipelineStateManager $state_manager) {
    $this->pipelinePluginManager = $pipeline_plugin_manager;
    $this->stateManager = $state_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.pipeline_pipeline'),
      $container->get('pipeline.state_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pipeline_select_pipeline';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->stateManager->isPersisted()) {
      $state = $this->stateManager->getState();
      return $this->redirect('pipeline.execute_pipeline', ['pipeline' => $state->getPipelineId()]);
    }

    $pipelines = [];
    foreach ($this->pipelinePluginManager->getDefinitions() as $plugin_id => $definition) {
      if ($this->currentUser()->hasPermission("execute $plugin_id pipeline")) {
        $pipelines[$plugin_id] = $definition['label'];
      }
    }

    $form['pipeline'] = [
      '#type' => 'select',
      '#title' => $this->t('Data pipeline'),
      '#options' => $pipelines,
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Execute'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('pipeline.execute_pipeline', ['pipeline' => $form_state->getValue('pipeline')]);
  }

}
