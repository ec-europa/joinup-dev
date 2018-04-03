<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin\EtlProcessStep;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rdf_etl\Plugin\EtlAdms2ConvertPassPluginManager;
use Drupal\rdf_etl\ProcessStepBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a step that creates provenance activity entities for related data.
 *
 * @EtlProcessStep(
 *  id = "attach_provenance_data",
 *  label = @Translation("Attach provenance data to the entities"),
 * )
 */
class AttachProvenanceData extends ProcessStepBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array &$data): void {
    $test = 1;
  }

}
