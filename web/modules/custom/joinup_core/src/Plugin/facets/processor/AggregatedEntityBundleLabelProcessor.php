<?php

namespace Drupal\joinup_core\Plugin\facets\processor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\FacetSource\SearchApiFacetSourceInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Replaces the bundle machine name with its label in aggregated fields.
 *
 * @FacetsProcessor(
 *   id = "aggregated_entity_bundle_label",
 *   label = @Translation("Transform aggregated entity bundle into label"),
 *   description = @Translation("Replaces the bundle machine name with its label in aggregated fields. Use only on aggregated bundle field."),
 *   stages = {
 *     "build" = 5
 *   }
 * )
 */
class AggregatedEntityBundleLabelProcessor extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    // Bail out early if the source is not coming from Search API.
    $source = $facet->getFacetSource();
    if (!$source instanceof SearchApiFacetSourceInterface) {
      return $results;
    }

    // Retrieve the configured datasources for the index.
    /** @var \Drupal\search_api\Plugin\search_api\datasource\ContentEntity[] $datasources */
    $datasources = $source->getIndex()->getDatasources();

    // Fetch all the bundles available in this index.
    // Notice that if there are two entity types that have a bundle with the
    // same id, the wrong label might be shown. Since this processor is only
    // meant for a field that consists of aggregated bundle ids, that situation
    // is unlikely going to happen.
    $bundles = [];
    foreach ($datasources as $datasource) {
      $bundles += $datasource->getBundles();
    }

    foreach ($results as $delta => $result) {
      $bundle_id = $result->getRawValue();
      if (!isset($bundles[$bundle_id])) {
        continue;
      }

      // We should use the singular/plural version of the bundle label but
      // there is no support yet.
      // @see https://www.drupal.org/node/2765065
      $result->setDisplayValue($bundles[$bundle_id]);
    }

    return $results;
  }

}
