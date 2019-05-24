<?php

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\joinup_federation\JoinupFederationAdms2ConvertPassPluginManager;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Plugin\PipelineStepWithBatchInterface;
use Drupal\pipeline\Plugin\PipelineStepWithBatchTrait;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a step that converts the imported data from ADMSv1 to ADMSv2.
 *
 * @PipelineStep(
 *   id = "convert_to_adms2",
 *   label = @Translation("Convert data from ADMS-AP v1 to v2"),
 * )
 */
class ConvertToAdms2 extends JoinupFederationStepPluginBase implements PipelineStepWithBatchInterface {

  use PipelineStepWithBatchTrait;

  /**
   * The batch size.
   *
   * @var int
   */
  const BATCH_SIZE = 2;

  /**
   * The ADMS v1 to v2 transformation plugin manager.
   *
   * @var \Drupal\joinup_federation\JoinupFederationAdms2ConvertPassPluginManager
   */
  protected $adms2ConverPassPluginManager;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql
   *   The SPARQL database connection.
   * @param \Drupal\joinup_federation\JoinupFederationAdms2ConvertPassPluginManager $adms2_conver_pass_plugin_manager
   *   The ADMS v1 to v2 transformation plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConnectionInterface $sparql, JoinupFederationAdms2ConvertPassPluginManager $adms2_conver_pass_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->adms2ConverPassPluginManager = $adms2_conver_pass_plugin_manager;
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
      $container->get('plugin.manager.joinup_federation_adms2_convert_pass')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initBatchProcess() {
    $plugin_ids = array_keys($this->adms2ConverPassPluginManager->getDefinitions());
    $this->setBatchValue('remaining_conversions', $plugin_ids);
    return ceil(count($plugin_ids) / static::BATCH_SIZE);
  }

  /**
   * {@inheritdoc}
   */
  public function batchProcessIsCompleted() {
    return !$this->getBatchValue('remaining_conversions');
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $conversions_to_process = $this->extractNextSubset('remaining_conversions', static::BATCH_SIZE);
    foreach ($conversions_to_process as $plugin_id) {
      $this->adms2ConverPassPluginManager
        ->createInstance($plugin_id)
        ->convert(['sink_graph' => $this->getGraphUri('sink')]);
    }
  }

}
