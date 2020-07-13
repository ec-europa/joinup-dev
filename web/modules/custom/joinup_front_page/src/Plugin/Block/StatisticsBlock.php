<?php

declare(strict_types = 1);

namespace Drupal\joinup_front_page\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\joinup_workflow\WorkflowHelperInterface;
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
   * The community content bundle ids.
   *
   * @var array
   */
  const COMMUNITY_BUNDLES = [
    'discussion',
    'document',
    'event',
    'news',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The workflow helper.
   *
   * @var \Drupal\joinup_workflow\WorkflowHelperInterface
   */
  protected $workflowHelper;

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
   * @param \Drupal\joinup_workflow\WorkflowHelperInterface $workflow_helper
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
      $container->get('joinup_workflow.workflow_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = ['#theme' => 'statistics_block'];

    foreach (['collection', 'solution', 'content'] as $type) {
      $build["#{$type}_count"] = $this->getCount($type);
    }

    return $build;
  }

  /**
   * Returns the number of published entities of the given type.
   *
   * @param string $type
   *   One of 'collection', 'solution' or 'content'.
   *
   * @return int
   *   The number of validated entities.
   */
  protected function getCount($type) {
    /** @var \Drupal\search_api\Entity\Index $index */
    $index = $this->entityTypeManager->getStorage('search_api_index')->load('published');
    /** @var \Drupal\search_api\Query\QueryInterface $query */
    $query = $index->query();
    switch ($type) {
      case 'collection':
        $query->addCondition('entity_bundle', 'collection');
        break;

      case 'solution':
        $query->addCondition('entity_bundle', 'solution');
        break;

      case 'content':
        $query->addCondition('entity_bundle', self::COMMUNITY_BUNDLES, 'IN');
        break;

    }
    // We don't need the actual items, just the count.
    $query->range(0, 0);
    $results = $query->execute();
    return $results->getResultCount();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Add the list cache tags of the entity types that are shown in the block,
    // so that the cache is invalidated whenever an entity is created or
    // deleted. This makes sure the count is always correct.
    $cache_tags = parent::getCacheTags();
    foreach (['node', 'rdf_entity'] as $type) {
      $entity_type = $this->entityTypeManager->getStorage($type)->getEntityType();
      $cache_tags = Cache::mergeTags($cache_tags, $entity_type->getListCacheTags());
    }
    return $cache_tags;
  }

}
