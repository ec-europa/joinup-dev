<?php

declare(strict_types = 1);

namespace Drupal\joinup_search\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds all ancestors' IDs to the topic aggregated hierarchical field.
 *
 * This is currently hardcoded on topics since it is the only hierarchical data
 * we are indexing right now. In the future this can be expanded to loop over
 * all fields and check if any are hierarchical.
 *
 * @SearchApiProcessor(
 *   id = "aggregated_topic_hierarchy",
 *   label = @Translation("Index aggregated topic hierarchy"),
 *   description = @Translation("Allows the indexing of values along with all their ancestors for the topic aggregated hierarchical fields."),
 *   stages = {
 *     "preprocess_index" = -50
 *   }
 * )
 */
class AddAggregatedTopicHierarchy extends ProcessorPluginBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an AddAggregatedTopicHierarchy plugin.
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
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
  public function preprocessIndexItems(array $items) {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    foreach ($items as $item) {
      if (!($field = $item->getField('topic'))) {
        continue;
      }

      $parent_tids = [];
      foreach ($values = $field->getValues() as $tid) {
        $parents = $storage->loadParents($tid);
        $parent_tids = array_merge($parent_tids, array_keys($parents));
      };

      $field->setValues(array_merge($values, $parent_tids));
    }
  }

}
