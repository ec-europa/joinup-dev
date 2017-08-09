<?php

namespace Drupal\joinup_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with the proposed collections and solutions.
 *
 * @Block(
 *   id = "proposed_entities",
 *   admin_label = @Translation("Rdf entities in proposed state")
 * )
 */
class ProposedEntitiesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ProposedEntitiesBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
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
    $rows = $this->getRows();
    if (empty($rows)) {
      return [];
    }
    $build = [
      // The 'listing' child key is needed to avoid copying the #attributes to
      // the parent block.
      // @see \Drupal\block\BlockViewBuilder::preRender()
      '#extra_suggestion' => 'separated_block',
      'listing' => [
        '#type' => 'container',
        '#extra_suggestion' => 'container_grid',
      ],
    ];

    $build['listing'] += $rows;
    return $build;
  }

  /**
   * Receives the unpublished content rows for the current user.
   *
   * @return array
   *   An array of rows to render.
   */
  protected function getRows() {
    /** @var \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage $storage */
    $storage = $this->entityTypeManager->getStorage('rdf_entity');
    $query = $storage->getQuery('OR');
    $solution_sub_condition = $query->andConditionGroup();
    $solution_sub_condition->condition('rid', 'solution');
    $solution_sub_condition->condition('field_is_state', 'proposed');
    $collection_sub_condition = $query->andConditionGroup();
    $collection_sub_condition->condition('rid', 'collection');
    $collection_sub_condition->condition('field_ar_state', 'proposed');
    $query->condition($solution_sub_condition);
    $query->condition($collection_sub_condition);
    $results = $query->execute();

    $data = array_fill_keys($results, ['draft']);
    $storage->setRequestGraphsMultiple($data);
    $entities = $storage->loadMultiple($results);
    $storage->getGraphHandler()->resetRequestGraphs($results);
    $rows = [];

    foreach ($entities as $entity) {
      $view = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())->view($entity, 'view_mode_tile');
      $rows[] = [
        '#type' => 'container',
        '#extra_suggestion' => 'container_grid_item',
        'entity' => $view,
      ];
    }
    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $rdf_type = $this->entityTypeManager->getStorage('rdf_entity')->getEntityType();
    return Cache::mergeTags(parent::getCacheTags(), $rdf_type->getListCacheTags());
  }

}
