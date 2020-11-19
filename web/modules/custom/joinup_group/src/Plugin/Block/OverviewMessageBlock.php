<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A block showing the introduction text on collection and solution overviews.
 *
 * @Block(
 *  id = "overview_message_block",
 *  admin_label = @Translation("Overview message"),
 * )
 */
class OverviewMessageBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Static cache of the RDF Entity Type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $rdfEntityTypeStorage;

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
  public function build(): array {
    $route_name = $this->currentRouteMatch->getRouteName();
    $build = [];
    if ($route_name === 'view.solutions.page_1') {
      $build['header_description'] = [
        '#type' => 'inline_template',
        '#template' => '<p>' . $this->getRdfEntityTypeStorage()->load('solution')->getDescription() . '</p>',
      ];
    }
    elseif ($route_name === 'view.collections.page_1') {
      $build['header_description'] = [
        '#type' => 'inline_template',
        '#template' => '<p>' . $this->getRdfEntityTypeStorage()->load('collection')->getDescription() . '</p>',
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    $cache_contexts = parent::getCacheContexts();
    return Cache::mergeContexts($cache_contexts, ['route']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), Cache::mergeTags(
      $this->getRdfEntityTypeStorage()->load('collection')->getCacheTagsToInvalidate(),
      $this->getRdfEntityTypeStorage()->load('solution')->getCacheTagsToInvalidate()
    ));
  }

  /**
   * Returns the RDF Entity Type storage.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   *   The RDF Entity Type storage.
   */
  protected function getRdfEntityTypeStorage(): ConfigEntityStorageInterface {
    if (!isset($this->rdfEntityTypeStorage)) {
      $this->rdfEntityTypeStorage = $this->entityTypeManager->getStorage('rdf_type');
    }
    return $this->rdfEntityTypeStorage;
  }

}
