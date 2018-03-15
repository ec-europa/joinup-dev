<?php

namespace Drupal\rdf_etl\Plugin\EtlProcessStep;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rdf_etl\Plugin\EtlAdms2ConvertPassPluginManager;
use Drupal\rdf_etl\ProcessStepBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a step that converts the imported data from ADMSv1 to ADMSv2.
 *
 * @EtlProcessStep(
 *   id = "convert_to_adms2",
 *   label = @Translation("Convert ADMSv1 to v2"),
 * )
 */
class ConvertToAdms2 extends ProcessStepBase implements ContainerFactoryPluginInterface {

  /**
   * The ADMS v1 to v2 transformation plugin manager.
   *
   * @var \Drupal\rdf_etl\Plugin\EtlAdms2ConvertPassPluginManager
   */
  protected $adms2ConverPassPluginManager;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\rdf_etl\Plugin\EtlAdms2ConvertPassPluginManager $adms2_conver_pass_plugin_manager
   *   The ADMS v1 to v2 transformation plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EtlAdms2ConvertPassPluginManager $adms2_conver_pass_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('plugin.manager.etl_adms2_convert_pass')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array &$data): void {
    $data += ['sink_graph' => $this->getConfiguration()['sink_graph']];
    // @todo There are ~75 passes, need to use batch processing?
    foreach ($this->adms2ConverPassPluginManager->getDefinitions() as $plugin_id => $definition) {
      /** @var \Drupal\rdf_etl\Plugin\EtlAdms2ConvertPassInterface $plugin */
      $plugin = $this->adms2ConverPassPluginManager->createInstance($plugin_id);
      $plugin->convert($data);
    }
  }

}
