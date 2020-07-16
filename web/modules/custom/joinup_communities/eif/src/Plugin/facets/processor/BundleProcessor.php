<?php

declare(strict_types = 1);

namespace Drupal\eif\Plugin\facets\processor;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\facets\Exception\InvalidProcessorException;
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
  public function build(FacetInterface $facet, array $results) {
    $config = $this->getConfiguration();
    [$entity_type_id, $bundle_key] = $this->getEntityTypeIdAndBundleKey($facet);

    $operator = $config['mode'] === 'include' ? 'IN' : 'NOT IN';
    $allowed_ids = $this->entityTypeManager->getStorage($entity_type_id)
      ->getQuery()
      ->condition($bundle_key, $config['bundle'], $operator)
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
    [$entity_type_id] = $this->getEntityTypeIdAndBundleKey($facet);
    $options = array_map(function (array $bundle_info): string {
      return $bundle_info['label'];
    }, $this->entityTypeBundleInfo->getBundleInfo($entity_type_id));

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
    $type = count($options) > 10 ? 'select' : 'checkboxes';
    $build['bundle'] = [
      '#type' => $type,
      '#title' => $this->t('Bundles'),
      '#description' => $this->t('If none is selected all bundles are allowed.'),
      '#options' => $options,
      '#default_value' => $config['bundle'],
    ];

    if ($type === 'select') {
      $build['bundle']['#multiple'] = TRUE;
      $build['bundle']['#size'] = 10;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'mode' => 'include',
      'bundle' => [],
    ];
  }

  /**
   * Returns the entity type ID and the bundle key of facet results, if any.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet this processor is being added to.
   *
   * @return array
   *   An indexed array with:
   *   - 0: The entity type ID.
   *   - 1: The bundle key.
   *
   * @throws \Drupal\facets\Exception\InvalidProcessorException
   *   Thrown when:
   *   - The processor is applied to a field without an entity definition.
   *   - The entity type doesn't declare a bundle key.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if when the entity type is invalid.
   */
  protected function getEntityTypeIdAndBundleKey(FacetInterface $facet): array {
    $data_definition = $facet->getDataDefinition();

    $property = NULL;
    foreach ($data_definition->getPropertyDefinitions() as $name => $definition) {
      if ($definition instanceof DataReferenceDefinitionInterface && $definition->getDataType() === 'entity_reference') {
        $property = $name;
        break;
      }
    }

    if ($property === NULL) {
      throw new InvalidProcessorException("Field doesn't have an entity definition, so this processor doesn't work.");
    }

    $entity_type_id = $data_definition
      ->getPropertyDefinition($property)
      ->getTargetDefinition()
      ->getEntityTypeId();

    // Support only entity types declaring a bundle key.
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if (!$entity_type->hasKey('bundle')) {
      throw new InvalidProcessorException("The '{$entity_type_id}' doesn't declare bundles.");
    }

    return [$entity_type_id, $entity_type->getKey('bundle')];
  }

}
