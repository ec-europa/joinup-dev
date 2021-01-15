<?php

declare(strict_types = 1);

namespace Drupal\joinup_front_page\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_group\JoinupGroupHelper;
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
   * Static cache for RDF Entity Type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $rdfEntityTypeStorage;

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
  public function build() {
    $build = ['#theme' => 'statistics_block'];

    foreach (['collection', 'solution', 'content'] as $type) {
      $build["#{$type}"] = [
        'count' => $this->getCount($type),
        'description' => $this->getDescription($type),
      ];
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
        $query->addCondition('entity_bundle', CommunityContentHelper::BUNDLES, 'IN');
        break;

    }
    // We don't need the actual items, just the count.
    $query->range(0, 0);
    $results = $query->execute();
    return $results->getResultCount();
  }

  /**
   * Returns the description of the given type.
   *
   * @param string $type
   *   One of 'collection', 'solution' or 'content'.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The type's description.
   */
  protected function getDescription(string $type) {
    if ($type === 'collection') {
      return $this->getRdfEntityTypeStorage()->load('collection')->getDescription();
    }
    elseif ($type === 'solution') {
      return $this->getRdfEntityTypeStorage()->load('solution')->getDescription();
    }
    return CommunityContentHelper::getCommunityContentDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Add the list cache tags of the entity types that are shown in the block,
    // so that the cache is invalidated whenever an entity is created or
    // deleted. This makes sure the count is always correct.
    $cache_tags = parent::getCacheTags();
    $entity_bundles = [
      'rdf_entity' => array_values(JoinupGroupHelper::GROUP_BUNDLES),
      'node' => CommunityContentHelper::BUNDLES,
    ];
    $cache_tags = Cache::mergeTags($cache_tags);
    foreach ($entity_bundles as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle) {
        $cache_tags = Cache::mergeTags($cache_tags, ["{$entity_type_id}_list:{$bundle}"]);
      }
    }

    // Add also the cache tags of collection and solution rdf_type entities.
    $cache_tags = Cache::mergeTags($cache_tags, $this->getRdfEntityTypeStorage()->load('collection')->getCacheTagsToInvalidate());
    $cache_tags = Cache::mergeTags($cache_tags, $this->getRdfEntityTypeStorage()->load('solution')->getCacheTagsToInvalidate());

    return $cache_tags;
  }

  /**
   * Returns the RDF Entity Type storage.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   *   RDF Entity Type storage.
   */
  protected function getRdfEntityTypeStorage(): ConfigEntityStorageInterface {
    if (!isset($this->rdfEntityTypeStorage)) {
      $this->rdfEntityTypeStorage = $this->entityTypeManager->getStorage('rdf_type');
    }
    return $this->rdfEntityTypeStorage;
  }

}
