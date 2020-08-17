<?php

declare(strict_types = 1);

namespace Drupal\eif\Plugin\facets\processor;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\facets\Result\ResultInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a processor that excludes items from a configurable set of bundles.
 *
 * @FacetsProcessor(
 *   id = "bundle",
 *   label = @Translation("Limit to bundles"),
 *   description = @Translation("Limit to a configurable set of bundles."),
 *   stages = {
 *     "build" = 50,
 *   },
 * )
 */
class BundleProcessor extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Static cache for facet data entity type ID.
   *
   * @var string|false
   */
  protected $entityTypeId;

  /**
   * Static cache for facet data entity type bundle key.
   *
   * @var string|false
   */
  protected $bundleKey;

  /**
   * Constructs a new processor plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
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
  public function supportsFacet(FacetInterface $facet): bool {
    return (bool) ($this->getEntityTypeId($facet) && $this->getBundleKey($facet));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'mode' => 'include',
      'bundle' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $config = $this->getConfiguration();

    $operator = $config['mode'] === 'include' ? 'IN' : 'NOT IN';
    $allowed_ids = $this->entityTypeManager
      ->getStorage($this->getEntityTypeId($facet))
      ->getQuery()
      ->condition($this->getBundleKey($facet), $config['bundle'], $operator)
      ->execute();

    $results = array_values(
      array_filter($results, function (ResultInterface $result) use ($allowed_ids): bool {
        return in_array($result->getRawValue(), $allowed_ids);
      })
    );

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $config = $this->getConfiguration();

    $bundles = array_map(function (array $bundle_info): string {
      return $bundle_info['label'];
    }, $this->entityTypeBundleInfo->getBundleInfo($this->getEntityTypeId($facet)));

    $build['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Selection mode'),
      '#description' => $this->t('Whether to allow entities from the selected bundle or to allow from all bundles excluding the selected ones.'),
      '#options' => [
        'include' => $this->t('Limit facets to selected bundles'),
        'exclude' => $this->t('Allow from all excluding selected bundles'),
      ],
      '#default_value' => $config['mode'],
    ];
    $type = count($bundles) > 10 ? 'select' : 'checkboxes';
    $build['bundle'] = [
      '#type' => $type,
      '#title' => $this->t('Bundles'),
      '#description' => $this->t('If none is selected all bundles are allowed.'),
      '#options' => $bundles,
      '#default_value' => $config['bundle'],
    ];

    if ($type === 'select') {
      $build['bundle']['#multiple'] = TRUE;
      $build['bundle']['#size'] = 10;
    }

    return $build;
  }

  /**
   * Returns the entity type ID of facet results, if any.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet this processor is being added to.
   *
   * @return string|false
   *   The entity type ID of facet results, if any. If this facet doesn't have
   *   an entity data definition, will return FALSE.
   */
  protected function getEntityTypeId(FacetInterface $facet) {
    if (!isset($this->entityTypeId)) {
      $data_definition = $facet->getDataDefinition();
      if (!$data_definition instanceof ComplexDataDefinitionInterface) {
        return FALSE;
      }

      $property = NULL;
      foreach ($data_definition->getPropertyDefinitions() as $name => $definition) {
        if ($definition instanceof DataReferenceDefinitionInterface && $definition->getDataType() === 'entity_reference') {
          $property = $name;
          break;
        }
      }

      if ($property === NULL) {
        // Field doesn't have an entity definition.
        return FALSE;
      }

      $this->entityTypeId = $data_definition
        ->getPropertyDefinition($property)
        ->getTargetDefinition()
        ->getEntityTypeId();
    }

    return $this->entityTypeId;
  }

  /**
   * Returns the entity type bundle key of facet results, if any.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet this processor is being added to.
   *
   * @return string|false
   *   The entity type bundle key of facet results, if any. If this facet
   *   doesn't have an entity data definition or the entity type doesn't support
   *   bundles, will return FALSE.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if when the entity type is invalid.
   */
  protected function getBundleKey(FacetInterface $facet) {
    if (!isset($this->bundleKey)) {
      $this->bundleKey = FALSE;
      if ($entity_type_id = $this->getEntityTypeId($facet)) {
        // Support only entity types declaring a bundle key.
        $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
        if ($entity_type->hasKey('bundle')) {
          $this->bundleKey = $entity_type->getKey('bundle');
        }
      }
    }

    return $this->bundleKey;
  }

}
