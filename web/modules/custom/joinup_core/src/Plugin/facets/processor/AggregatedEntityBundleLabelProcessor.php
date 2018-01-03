<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Plugin\facets\processor;

use Drupal\Core\Config\Entity\EntityBundleWithPluralLabelsInterface;
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ProcessorInterface {
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

    // Fetch all the bundles available in this index.
    // Notice that if there are two entity types that have a bundle with the
    // same id, the wrong label might be shown. Since this processor is only
    // meant for a field that consists of aggregated bundle ids, that situation
    // is unlikely going to happen.
    $bundles = [];
    foreach ($datasources as $datasource) {
      // The values of the $bundles array are either bundle entities, for those
      // having plural count labels, or the standard bundle label for the
      // others. The keys are the bundle IDs. By giving precedence to bundles
      // with plural count labels we assure the standard bundle label as
      // fallback, in case a plural count label is missed.
      $bundles += $this->getBundlesWithLabelPluralCount($datasource->getEntityTypeId()) + $datasource->getBundles();
    }

    $plural_count_label_context = $this->getConfiguration()['plural_count_label']['context'];
    foreach ($results as $delta => $result) {
      $bundle_id = $result->getRawValue();
      if (!isset($bundles[$bundle_id])) {
        continue;
      }

      if ($bundles[$bundle_id] instanceof EntityBundleWithPluralLabelsInterface) {
        $result->setDisplayValue($bundles[$bundle_id]->getCountLabel($result->getCount(), $plural_count_label_context));
      }
      else {
        $result->setDisplayValue($bundles[$bundle_id]);
      }
    }

    return $results;
  }

  /**
   * Returns a list of bundles allowing and having label count plural variants.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\Core\Config\Entity\EntityBundleWithPluralLabelsInterface[]
   *   A list of bundle entities keyed by bundle entity ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   If the bundle entity type did not specify a storage handler.
   */
  protected function getBundlesWithLabelPluralCount(string $entity_type_id): array {
    // Are plural labels requested by plugin configuration?
    if ($this->getConfiguration()['plural_count_label']['enabled']) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      // Not all bundles are defined as config entities.
      if ($bundle_entity_type_id = $entity_type->getBundleEntityType()) {
        // Ensure there's valid entity type for this bundle entity type ID.
        if ($bundle_entity_type = $this->entityTypeManager->getDefinition($bundle_entity_type_id)) {
          $bundle_class = $bundle_entity_type->getClass();
          $bundle_class_interfaces = class_implements($bundle_class);
          // Limit to entity types with bundle entities allowing label plurals.
          if (in_array(EntityBundleWithPluralLabelsInterface::class, $bundle_class_interfaces)) {
            if ($bundle_entity_storage = $this->entityTypeManager->getStorage($bundle_entity_type_id)) {
              // Get all the bundle config entities of this entity type.
              $bundles = $bundle_entity_storage->loadMultiple();
              $plural_count_label_context = $this->getConfiguration()['plural_count_label']['context'];

              // Filter out bundles with count label returning an empty value.
              return array_filter($bundles, function (EntityBundleWithPluralLabelsInterface $bundle) use ($plural_count_label_context): bool {
                // Ensure at least the singular and one plural.
                return !empty($bundle->getCountLabel(1, $plural_count_label_context)) && !empty($bundle->getCountLabel(2, $plural_count_label_context));
              });
            }
          }
        }
      }
    }
    return [];
  }

}
