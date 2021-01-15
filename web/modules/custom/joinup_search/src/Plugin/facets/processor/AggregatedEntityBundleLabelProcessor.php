<?php

declare(strict_types = 1);

namespace Drupal\joinup_search\Plugin\facets\processor;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\FacetSource\SearchApiFacetSourceInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorInterface;
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
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

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
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ProcessorInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'plural_count_label' => [
        'enabled' => FALSE,
        'context' => NULL,
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet): array {
    return [
      'plural_count_label' => [
        'enabled' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Show the bundle label as plural count variant'),
          '#description' => $this->t('If checked, the plural count variant of the bundle label will be used, instead of the normal bundle entity label. This label will show the singular variant if the number of count equals 1, or the appropriate plural variant if the count is greater than 1.'),
          '#default_value' => $this->getConfiguration()['plural_count_label']['enabled'],
        ],
        'context' => [
          '#type' => 'textfield',
          '#title' => $this->t('Plural count label context to be used'),
          '#description' => $this->t('Multiple plural count labels could be defined on the system. Specify the context to identify a particular version, or leave empty to use the default variant.'),
          '#default_value' => $this->getConfiguration()['plural_count_label']['context'],
          '#states' => [
            'visible' => [
              ':input[name="facet_settings[' . $this->getPluginId() . '][settings][plural_count_label][enabled]"]' => ['checked' => TRUE],
            ],
          ],
        ],
      ],
    ] + parent::buildConfigurationForm($form, $form_state, $facet);
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results): array {
    // Avoid processing if there's no result.
    if (!$results) {
      return [];
    }

    // Bail out early if the source is not coming from Search API.
    $source = $facet->getFacetSource();
    if (!$source instanceof SearchApiFacetSourceInterface) {
      return $results;
    }

    // Retrieve the configured datasources for the index.
    /** @var \Drupal\search_api\Plugin\search_api\datasource\ContentEntity[] $datasources */
    $datasources = $source->getIndex()->getDatasources();

    // Fetch all the bundles available in this index. Notice that if there are
    // two entity types that have a bundle with the same ID, the wrong label
    // might be shown. Since this processor is only meant for a field that
    // consists of aggregated bundle IDs, that situation is unlikely going to
    // happen.
    $bundles = [];
    foreach ($datasources as $datasource) {
      $bundle_info = $this->bundleInfo->getBundleInfo($datasource->getEntityTypeId());
      foreach ($datasource->getBundles() as $bundle_id => $bundle_label) {
        $bundles[$bundle_id]['entity_type_id'] = $datasource->getEntityTypeId();
        $bundles[$bundle_id]['label'] = $bundle_label;
        if (!empty($bundle_info[$bundle_id]['label_count'])) {
          $bundles[$bundle_id]['has_count_label'] = TRUE;
        }
      }
    }

    $plural_count_label_variant = $this->getConfiguration()['plural_count_label']['context'];
    foreach ($results as $result) {
      $bundle_id = $result->getRawValue();
      if (!isset($bundles[$bundle_id])) {
        continue;
      }

      if (!empty($bundles[$bundle_id]['has_count_label'])) {
        $result->setDisplayValue($this->bundleInfo->getBundleCountLabel($bundles[$bundle_id]['entity_type_id'], $bundle_id, $result->getCount(), $plural_count_label_variant));
      }
      else {
        $result->setDisplayValue($bundles[$bundle_id]['label']);
      }
    }

    return $results;
  }

}
