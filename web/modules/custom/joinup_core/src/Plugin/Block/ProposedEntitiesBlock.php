<?php

namespace Drupal\joinup_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with the recommended community content for the current user.
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
   * Constructs a new RecommendedContentBlock object.
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
      'listing' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['listing', 'listing--grid', 'mdl-grid'],
        ],
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
    $query = $this->entityTypeManager->getStorage('rdf_entity')->getQuery();
    $query->condition('field_state', 'proposed');
    $results = $query->execute();
    $entities = Rdf::loadMultiple($results);
    $rows = [];

    foreach ($entities as $weight => $entity) {
      $view = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())->view($entity, 'view_mode_tile');
      $rows[$weight] = [
        '#type' => 'container',
        '#weight' => $weight,
        '#attributes' => [
          'class' => [
            'listing__item',
            'listing__item--tile',
            'mdl-cell',
            'mdl-cell--4-col',
          ],
        ],
        $weight => $view,
      ];
    }
    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['rdf_entity_list']);
  }

}
