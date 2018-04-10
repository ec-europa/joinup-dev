<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Plugin\PipelineStepWithFormInterface;
use Drupal\pipeline\Plugin\PipelineStepWithFormTrait;
use Drupal\rdf_entity\RdfEntityGraphStoreTrait;
use EasyRdf\Graph;

/**
 * Defines a manual data upload step plugin.
 *
 * @PipelineStep(
 *  id = "manual_upload_step",
 *  label = @Translation("Manual upload"),
 * )
 */
class ManualUploadStep extends JoinupFederationStepPluginBase implements PipelineStepWithFormInterface {

  use PipelineStepWithFormTrait;
  use RdfEntityGraphStoreTrait;

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$data) {
    parent::prepare($data);
    $this->pipeline->clearGraphs();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array &$data) {
    try {
      $this->createGraphStore()->replace($data['graph'], $this->getGraphUri('sink'));
      $data['adms_file']->delete();
    }
    catch (\Exception $exception) {
      return $this->t('Could not store triples in triple store. Reason: @message', [
        '@message' => $exception->getMessage(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['adms_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('File'),
      '#description' => $this->t('Please upload a file to use for federation. Allowed types: @extensions.', [
        '@extensions' => '*.rdf, *.ttl',
      ]),
      '#upload_validators'  => [
        'file_validate_extensions' => ['rdf ttl'],
      ],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    if (!$file = $this->getFile($form_state)) {
      $form_state->setError($form['adms_file'], 'Please upload a valid RDF file.');
      return;
    }

    try {
      $form_state->set('adms_file', $file);
      $form_state->set('graph', $this->fileToGraph($file));
    }
    catch (\Exception $e) {
      $form_state->setError($form['adms_file'], 'The provided file is not a valid RDF file.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function extractDataFromSubmit(FormStateInterface $form_state): array {
    return [
      'adms_file' => $form_state->get('adms_file'),
      'graph' => $form_state->get('graph'),
    ];
  }

  /**
   * Builds a RDF graph from a file object.
   *
   * @param \Drupal\file\FileInterface $file
   *   The to be validated file.
   *
   * @return \EasyRdf\Graph
   *   A collection of triples.
   */
  protected function fileToGraph(FileInterface $file): Graph {
    $graph = new Graph();
    $graph->parseFile($file->getFileUri());
    return $graph;
  }

  /**
   * Returns the file entity from the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\file\FileInterface|null
   *   The uploaded file entity.
   */
  protected function getFile(FormStateInterface $form_state): ?FileInterface {
    $adms_files = $form_state->getValue('adms_file');
    if (!isset($adms_files[0]) || !$file = File::load($adms_files[0])) {
      return NULL;
    }
    return $file;
  }

}
