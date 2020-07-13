<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\joinup_licence\LicenceComparerHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the block that displays the menu containing group pages.
 *
 * @Block(
 *   id = "licence_filter_block",
 *   admin_label = @Translation("Licence filter block"),
 *   category = @Translation("Joinup")
 * )
 */
class LicenceFilterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new LicenceFilterBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
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
  public function build(): array {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $storage->loadTree('legal_type');
    $tree = [];
    foreach ($terms as $term) {
      $parent = reset($term->parents);
      if (empty($parent)) {
        $tree[$term->tid]['title'] = $term->name;
        $tree[$term->tid]['class'] = 'licence-filter--' . strtolower($term->name);
      }
      else {
        $child = $storage->load($term->tid);
        $tree[$parent]['items'][] = [
          'title' => $term->name,
          'description' => $child->getDescription(),
          'licence_category' => htmlentities(str_replace([' ', '/'], ['-', '-'], strtolower($term->name))),
        ];
      }
    }

    if (empty($tree)) {
      return [];
    }

    $build['tree_filters'] = [
      '#theme' => 'licence_filter_list',
      '#items' => $tree,
    ];

    $build['input_search'] = [
      '#theme' => 'licence_filter_search_input',
    ];
    $build['#cache']['max-age'] = 0;

    $build['#attached']['drupalSettings']['licenceComparer'] = [
      'path' => '/licence/compare',
      'maxLicenceCount' => LicenceComparerHelper::MAX_LICENCE_COUNT,
    ];
    $build['#attached']['library'][] = 'joinup_licence/licence-filter';

    return $build;
  }

}
