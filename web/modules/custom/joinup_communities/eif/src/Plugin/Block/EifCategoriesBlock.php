<?php

declare(strict_types = 1);

namespace Drupal\eif\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\eif\EifInterface;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a navigator for EIF Toolbox solutions page navigator.
 *
 * @Block(
 *   id = "eif_categories",
 *   admin_label = @Translation("EIF categories"),
 *   category = @Translation("EIF"),
 * )
 */
class EifCategoriesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The SPARQL connection.
   *
   * @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface
   */
  protected $sparql;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Constructs a new block plugin instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql
   *   The SPARQL connection.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route match service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, ConnectionInterface $sparql, CurrentRouteMatch $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sparql = $sparql;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sparql.endpoint'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // We're doing a direct SPARQL query as is hard to group on a multi-value
    // field using the SPARQL entity query.
    $results = $this->sparql->query('SELECT DISTINCT(?category)
      WHERE {
        ?solution_id a <http://www.w3.org/ns/dcat#Dataset> .
        ?solution_id <http://joinup.eu/eif/categories> ?category .
      }');

    $categories = array_flip(array_map(function (\stdClass $row): string {
      return $row->category->getValue();
    }, $results->getArrayCopy()));

    $active_category = $this->routeMatch->getParameter('eif_category');
    $category_links = [];
    foreach (EifInterface::EIF_CATEGORIES as $category => $label) {
      if (isset($categories[$category])) {
        $category_link = [
          '#type' => 'link',
          '#title' => $label,
          '#url' => Url::fromRoute('eif.solutions', [
            'node' => EifInterface::EIF_SOLUTIONS_NID,
            'eif_category' => $category,
          ]),
        ];

        if (!empty($active_category) && $active_category === $category) {
          $category_link['#attributes']['class'][] = 'is-active';
        }
        $category_links[] = $category_link;
      }
    }

    $all_link = [
      '#type' => 'link',
      '#title' => $this->t('All'),
      '#url' => Url::fromRoute('entity.node.canonical', [
        'node' => EifInterface::EIF_SOLUTIONS_NID,
      ]),
    ];
    if (empty($active_category)) {
      $all_link['#attributes']['class'][] = 'is-active';
    }

    return [
      [
        '#theme' => 'eif_category_navigator',
        '#all_link' => $all_link,
        '#category_links' => $category_links,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeTags(parent::getCacheContexts(), ['url.path']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), [
      'rdf_entity_list:solution',
    ]);
  }

}
