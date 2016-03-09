<?php

/**
 * @file
 * Contains \Drupal\joinup\Plugin\Block\AddContentBlock.
 */

namespace Drupal\joinup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;

/**
 * Provides an 'AddContentBlock' block.
 *
 * @Block(
 *  id = "add_content_block",
 *  admin_label = @Translation("Add content"),
 * )
 */
class AddContentBlock extends BlockBase
{

  /**
   * The collection to join.
   *
   * @var \Drupal\rdf_entity\RdfInterface
   */
  protected $collection;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Constructs a JoinCollectionBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match service.
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = \Drupal::routeMatch();

    // @todo: This should be restricted to collection rdf_entities only.
    // Retrieve the collection from the route.
    $this->collection = $this->currentRouteMatch->getParameter('rdf_entity');
  }

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    $build = [
      'collection' => [
        '#type' => 'link',
        '#title' => $this->t('Propose collection'),
        '#url' => Url::fromRoute('collection.propose_form'),
        '#attributes' => ['class' => ['button', 'button--small']],
      ],
    ];

    if (!empty($this->collection) && $this->collection->bundle() == 'collection') {
      $build['custom_page'] = [
        '#type' => 'link',
        '#title' => $this->t('Add custom page'),
        '#url' => Url::fromRoute('collection.custom_page.add',
          ['rdf_entity' => $this->currentRouteMatch->getRawParameter('rdf_entity')]),
        '#attributes' => ['class' => ['button', 'button--small']],
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // This block varies per user.
    $contexts = parent::getCacheContexts();
    return Cache::mergeContexts($contexts, ['url']);
  }

}
