<?php

namespace Drupal\joinup_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\joinup_core\WorkflowHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the block that shows statistics on the homepage of anonymous users.
 *
 * @Block(
 *  id = "statistics",
 *  admin_label = @Translation("Statistics block"),
 * )
 */
class StatisticsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The workflow helper.
   *
   * @var \Drupal\joinup_core\WorkflowHelperInterface
   */
  protected $workflowHelper;

  /**
   * Data about the statistics to show in the block.
   *
   * @var array
   *   An associative array of statistic types, each containing the entity type
   *   ID and bundles that are associated with this statistic type.
   */
  protected static $statisticsData = [
    'collection' => [
      'entity_type_id' => 'rdf_entity',
      'bundle_ids' => ['collection'],
    ],
    'solution' => [
      'entity_type_id' => 'rdf_entity',
      'bundle_ids' => ['solution'],
    ],
    'content' => [
      'entity_type_id' => 'node',
      'bundle_ids' => ['discussion', 'event', 'news'],
    ],
  ];

  /**
   * Constructs a new StatisticsBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\joinup_core\WorkflowHelperInterface $workflow_helper
   *   The workflow helper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, WorkflowHelperInterface $workflow_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->workflowHelper = $workflow_helper;
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
      $container->get('joinup_core.workflow.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = ['#theme' => 'statistics_block'];

    foreach (static::$statisticsData as $key => $data) {
      $build["#{$key}_count"] = $this->getCount($data['entity_type_id'], $data['bundle_ids']);
    }

    return $build;
  }

  /**
   * Returns the number of validated entities of the given type and bundles.
   *
   * @param string $entity_type_id
   *   The entity type ID for which to return the count.
   * @param array $bundle_ids
   *   The bundle IDs for which to return the count.
   *
   * @return int
   *   The number of validated entities.
   */
  protected function getCount($entity_type_id, array $bundle_ids) {
    // Retrieve the list of workflow state fields for the given bundle.
    $state_field_names = [];
    foreach ($bundle_ids as $bundle_id) {
      $state_field_definition = $this->workflowHelper->getEntityStateFieldDefinition($entity_type_id, $bundle_id);
      $state_field_name = $state_field_definition->getName();
      $state_field_names[$state_field_name] = $state_field_name;
    }
    $bundle_key = $this->entityTypeManager->getDefinition($entity_type_id)->getKey('bundle');

    $query = $this->entityTypeManager->getStorage($entity_type_id)->getQuery();
    $query->condition($bundle_key, $bundle_ids, 'IN');
    // Only show validated entities.
    foreach ($state_field_names as $state_field_name) {
      $query->condition($state_field_name, 'validated');
    }
    return $query
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Add the list cache contexts of the entity types that are shown in the
    // block, so that the cache is invalidated whenever an entity is created or
    // deleted. This makes sure the count is always correct.
    $cache_contexts = parent::getCacheContexts();

    foreach (static::$statisticsData as $data) {
      $entity_type = $this->entityTypeManager->getStorage($data['entity_type_id'])->getEntityType();
      $cache_contexts = Cache::mergeContexts($cache_contexts, $entity_type->getListCacheContexts());
    }

    return $cache_contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Add the list cache tags of the entity types that are shown in the block,
    // so that the cache is invalidated whenever an entity is created or
    // deleted. This makes sure the count is always correct.
    $cache_tags = parent::getCacheTags();

    foreach (static::$statisticsData as $data) {
      $entity_type = $this->entityTypeManager->getStorage($data['entity_type_id'])->getEntityType();
      $cache_tags = Cache::mergeTags($cache_tags, $entity_type->getListCacheTags());
    }

    return $cache_tags;
  }

}
