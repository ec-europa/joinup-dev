<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\adms_validator\AdmsValidatorInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Exception\PipelineStepExecutionLogicException;
use Drupal\pipeline\Plugin\PipelineStepWithBatchInterface;
use Drupal\pipeline\Plugin\PipelineStepWithBatchTrait;
use Drupal\pipeline\Plugin\PipelineStepWithRedirectResponseTrait;
use Drupal\pipeline\Plugin\PipelineStepWithResponseInterface;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a pipeline step that validates uploaded data.
 *
 * @PipelineStep(
 *   id = "adms_validation",
 *   label = @Translation("ADMS Validation"),
 * )
 */
class AdmsValidation extends JoinupFederationStepPluginBase implements PipelineStepWithResponseInterface, PipelineStepWithBatchInterface {

  use PipelineStepWithRedirectResponseTrait;
  use PipelineStepWithBatchTrait;

  /**
   * The batch size.
   *
   * @var int
   */
  const BATCH_SIZE = 1;

  /**
   * The ADMS validator service.
   *
   * @var \Drupal\adms_validator\AdmsValidatorInterface
   */
  protected $admsValidator;

  /**
   * Constructs a new 'adms_validation' pipeline step plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql
   *   The SPARQL database connection.
   * @param \Drupal\adms_validator\AdmsValidatorInterface $adms_validator
   *   The ADMS validator service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Connection $sparql, AdmsValidatorInterface $adms_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->admsValidator = $adms_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sparql_endpoint'),
      $container->get('adms_validator.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initBatchProcess() {
    $this->setBatchValue('single_iteration', self::BATCH_SIZE);
    return self::BATCH_SIZE;
  }

  /**
   * {@inheritdoc}
   */
  public function batchProcessIsCompleted() {
    return !$this->getBatchValue('single_iteration');
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $this->setBatchValue('single_iteration', 0);
    $graph_uri = $this->getGraphUri('sink_plus_taxo');
    $validation = $this->admsValidator->validateByGraphUri($graph_uri);

    // Cleanup the 'sink_plus_taxo' graph.
    $this->pipeline->clearGraph($this->getGraphUri('sink_plus_taxo'));

    if ($validation->isSuccessful()) {
      return;
    }

    throw (new PipelineStepExecutionLogicException())->setError([
      [
        '#markup' => $this->t('Imported data is not ADMS v2 compliant:'),
      ],
      $validation->toTable(),
    ]);
  }

}
