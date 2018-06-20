<?php

namespace Drupal\joinup_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Provides a 'OverviewMessageBlock' block.
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $current_route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $route_name = $this->currentRouteMatch->getRouteName();
    $build = [];
    if ($route_name === 'view.solutions.page_1') {
      $build['header_description'] = [
        '#type' => 'inline_template',
        '#template' => '<p>A solution on Joinup is a framework, tool, or service either hosted directly on Joinup or federated from third-party repositories.</p>',
      ];
    }
    elseif ($route_name === 'view.collections.page_1') {
      $build['header_description'] = [
        '#type' => 'inline_template',
        '#template' => '<p>Collections are the main collaborative space where the content items are organised around a common topic or domain and where the users can share their content and engage their community.</p>',
      ];
    }

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
