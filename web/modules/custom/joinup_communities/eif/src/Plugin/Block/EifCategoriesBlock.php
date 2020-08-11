<?php

declare(strict_types = 1);

namespace Drupal\eif\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\eif\Eif;
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
   * The EIF helper service.
   *
   * @var \Drupal\eif\EifInterface
   */
  protected $eifHelper;

  /**
   * The SPARQL connection.
   *
   * @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface
   */
  protected $sparql;

  /**
   * Constructs a new block plugin instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\eif\EifInterface $eif_helper
   *   The EIF helper service.
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql
   *   The SPARQL connection.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, EifInterface $eif_helper, ConnectionInterface $sparql) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eifHelper = $eif_helper;
    $this->sparql = $sparql;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eif.helper'),
      $container->get('sparql.endpoint')
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

    $category_links = [];
    foreach ($this->eifHelper->getEifCategories() as $category => $label) {
      if (isset($categories[$category])) {
        $category_links[] = [
          '#type' => 'link',
          '#title' => $label,
          '#url' => Url::fromRoute('view.eif_solutions.page', [
            'rdf_entity' => Eif::EIF_ID,
            'arg_1' => $category,
          ]),
        ];
      }
    }

    return [
      [
        '#theme' => 'eif_category_navigator',
        '#all_link' => [
          '#type' => 'link',
          '#title' => $this->t('All'),
          '#url' => Url::fromRoute('view.eif_solutions.page', [
            'rdf_entity' => Eif::EIF_ID,
          ]),
        ],
        '#category_links' => $category_links,
      ],
      '#cache' => [
        'tags' => [
          'rdf_entity_list:solution',
        ],
      ],
    ];
  }

}
