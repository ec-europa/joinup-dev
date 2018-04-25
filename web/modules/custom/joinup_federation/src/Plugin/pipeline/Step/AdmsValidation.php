<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\adms_validator\AdmsValidatorInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
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
class AdmsValidation extends JoinupFederationStepPluginBase {

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
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql
   *   The SPARQL database connection.
   * @param \Drupal\adms_validator\AdmsValidatorInterface $adms_validator
   *   The ADMS validator service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $sparql, AdmsValidatorInterface $adms_validator) {
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
  public function execute(array &$data) {
    $graph_uri = $this->getGraphUri('sink_plus_taxo');
    $validation = $this->admsValidator->validateByGraphUri($graph_uri);

    if ($validation->isSuccessful()) {
      return NULL;
    }

    return [
      [
        '#markup' => $this->t('Imported data is not ADMS v2 compliant:'),
      ],
      $validation->toTable(),
    ];
  }

}
