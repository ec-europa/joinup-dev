<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin\rdf_etl\Step;

use Drupal\adms_validator\AdmsValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rdf_entity\RdfEntityGraphStoreTrait;
use Drupal\rdf_etl\Plugin\RdfEtlStepPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a process step that validates uploaded data.
 *
 * @RdfEtlStep(
 *  id = "adms_validation",
 *  label = @Translation("ADMS Validation"),
 * )
 */
class AdmsValidation extends RdfEtlStepPluginBase implements ContainerFactoryPluginInterface {

  use RdfEntityGraphStoreTrait;

  /**
   * The ADMS validator service.
   *
   * @var \Drupal\adms_validator\AdmsValidatorInterface
   */
  protected $admsValidator;

  /**
   * Constructs a new 'adms_validation' process step plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\adms_validator\AdmsValidatorInterface $adms_validator
   *   The ADMS validator service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AdmsValidatorInterface $adms_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('adms_validator.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array &$data): void {
    $graph_uri = $this->getConfiguration()['sink_graph'];
    $graph = $this->createGraphStore()->get($graph_uri);
    $validation = $this->admsValidator->validateGraph($graph);
    if (!$validation->isSuccessful()) {
      $data['error'] = [
        [
          '#markup' => $this->t('Imported data is not ADMS v2 compliant:'),
        ],
        $validation->toTable(),
      ];
    }
  }

}
