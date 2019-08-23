<?php

namespace Drupal\eira\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Provides a 'EiraDeprecatedTermBlock' block.
 *
 * @Block(
 *  id = "eira_derprecated_term_block",
 *  admin_label = @Translation("Eira deprecated term message"),
 * )
 */
class EiraDeprecatedTermBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new OverviewMessageBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $current_route_match, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $current_route_match;
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
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $route_name = $this->currentRouteMatch->getRouteName();
    if ($route_name !== 'entity.taxonomy_term.canonical') {
      return [];
    }

    $term = $this->currentRouteMatch->getParameter('taxonomy_term');
    if (empty($term) || $term->bundle() !== 'eira') {
      return [];
    }

    $deprecated = (bool) $term->get('field_taxonomy_deprecated')->value;
    if (!$deprecated) {
      return [];
    }

    $taxonomy_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $query = $taxonomy_storage->getQuery();
    $results = $query->condition('field_taxonomy_replaces', $term->id())
      ->condition('bundle', 'eira')
      ->execute();

    if (empty($results)) {
      $message = $this->t('This building block is deprecated, and should not be used in new development.');
    }
    else {
      $replacement_id = reset($results);
      $replacement = $taxonomy_storage->load($replacement_id);
      $message = $this->t('This building block is deprecated, and should not be used in new development. Consider using @title (@uri) instead.', [
        '@uri' => $replacement->id(),
        '@title' => $replacement->label(),
      ]);
    }

    $build = [
      '#theme' => 'status_messages',
      '#message_list' => [
        'warning' => [$message],
      ],
      '#status_headings' => [
        'warning' => $this->t('Warning message'),
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = parent::getCacheContexts();
    return Cache::mergeContexts($cache_contexts, ['route']);
  }

}
