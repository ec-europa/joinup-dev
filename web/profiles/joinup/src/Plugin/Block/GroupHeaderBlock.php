<?php

namespace Drupal\joinup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'GroupHeaderBlock' block.
 *
 * @Block(
 *   id = "group_header_block",
 *   admin_label = @Translation("Group header"),
 *   context = {
 *     "og" = @ContextDefinition("og", label = @Translation("Organic group"))
 *   }
 * )
 */
class GroupHeaderBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct.
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
    /** @var \Drupal\Core\Entity\EntityInterface $group */
    $group = $this->getContext('og')->getContextValue();
    $view_builder = $this->entityTypeManager->getViewBuilder($group->getEntityTypeId());

    // We render the related view mode only in this block. Additional modules,
    // like collection and solution, will add more elements directly.
    $build['group'] = $view_builder->view($group, 'group_header');

    // Provide contextual links.
    $build['#contextual_links'] = [
      // Standard link to edit the group.
      'rdf_entity' => [
        'route_parameters' => [
          'rdf_entity' => $group->id(),
        ],
        'metadata' => ['changed' => $group->getChangedTime()],
      ],
      // Custom link to moderate content.
      'group_header_block' => [
        'route_parameters' => [
          'rdf_entity' => $group->id(),
        ],
        'metadata' => ['changed' => $group->getChangedTime()],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // The block by itself doesn't really vary by user, but some of its
    // implementations are (collection module, I'm looking at you). For the sake
    // of semplicity, we add the user context here already.
    $contexts = parent::getCacheContexts();
    return Cache::mergeContexts($contexts, ['user']);
  }

}
