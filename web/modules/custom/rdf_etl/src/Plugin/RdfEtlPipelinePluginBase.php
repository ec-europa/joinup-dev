<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rdf_etl\PipelineStepDefinitionInterface;
use Drupal\rdf_etl\RdfEtlStepList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for pipeline plugins.
 */
abstract class RdfEtlPipelinePluginBase extends PluginBase implements RdfEtlPipelineInterface, ContainerFactoryPluginInterface {

  /**
   * The execution order of the pipeline.
   *
   * @var \Drupal\rdf_etl\RdfEtlStepList
   */
  public $steps;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\rdf_etl\Plugin\RdfEtlStepPluginManager $step_plugin_manager
   *   The step plugin manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RdfEtlStepPluginManager $step_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->steps = new RdfEtlStepList();
    foreach ($this->getPluginDefinition()['steps'] as $step_plugin_id) {
      if (!$step_plugin_manager->hasDefinition($step_plugin_id)) {
        throw new \InvalidArgumentException("Invalid step plugin '$step_plugin_id'.");
      }
      $this->steps->add($step_plugin_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.rdf_etl_step')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function stepDefinitionList(): RdfEtlStepList {
    return $this->steps;
  }

  /**
   * {@inheritdoc}
   */
  public function getStepPluginId(int $sequence): string {
    return $this->steps->get($sequence);
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveStep(int $sequence): void {
    $this->steps->seek($sequence);
  }

}
