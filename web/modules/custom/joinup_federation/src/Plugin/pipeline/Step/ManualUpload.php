<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Exception\PipelineStepExecutionLogicException;
use Drupal\pipeline\Plugin\PipelineStepWithRedirectResponseTrait;
use Drupal\pipeline\Plugin\PipelineStepWithFormInterface;
use Drupal\pipeline\Plugin\PipelineStepWithFormTrait;
use Drupal\pipeline\Plugin\PipelineStepWithResponseInterface;
use Drupal\rdf_entity\RdfEntityGraphStoreTrait;
use EasyRdf\Graph;

/**
 * Defines a manual data upload step plugin.
 *
 * @PipelineStep(
 *   id = "manual_upload",
 *   label = @Translation("Manual upload"),
 * )
 */
class ManualUpload extends JoinupFederationStepPluginBase implements PipelineStepWithFormInterface, PipelineStepWithResponseInterface {

  use PipelineStepWithFormTrait;
  use PipelineStepWithRedirectResponseTrait;
  use RdfEntityGraphStoreTrait;

  /**
   * {@inheritdoc}
   */
  public function execute() {
    try {
      $fid = $this->getPersistentDataValue('fid');
      $this->unsetPersistentDataValue('fid');
      $this->createGraphStore()->replace($this->fileToGraph($fid));
      // We don't persist this value in the persistent data store.
      $this->unsetPersistentDataValue('fid');
    }
    catch (\Exception $exception) {
      throw (new PipelineStepExecutionLogicException())->setError([
        '#markup' => $this->t('Could not store triples in triple store. Reason: @message', [
          '@message' => $exception->getMessage(),
        ]),
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
    if (!isset($form_state->getValue('adms_file')[0])) {
      $form_state->setError($form['adms_file'], 'Please upload a valid RDF file.');
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAdditionalPersistentDataStore(FormStateInterface $form_state): array {
    return ['fid' => (int) $form_state->getValue('adms_file')[0]];
  }

  /**
   * Builds a RDF graph from a file.
   *
   * @param int $fid
   *   The file ID.
   *
   * @return \EasyRdf\Graph
   *   A collection of triples.
   */
  protected function fileToGraph(int $fid): Graph {
    $file = File::load($fid);
    $graph = new Graph($this->getGraphUri('sink'));
    $graph->parseFile($file->getFileUri());
    $file->delete();
    return $graph;
  }

}
