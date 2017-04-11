<?php

namespace Drupal\search_api_field\Plugin\facets\facet_source;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Url;
use Drupal\facets\FacetSource\SearchApiFacetSourceInterface;
use Drupal\facets\Plugin\facets\facet_source\SearchApiBaseFacetSource;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Query\ResultSetInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Represents a facet source which represents search_api_page pages.
 *
 * Most of the work of actually getting a page is done in the deriver.
 *
 * @FacetsFacetSource(
 *   id = "search_api_field",
 *   deriver = "Drupal\search_api_field\Plugin\facets\facet_source\SearchApiFieldDeriver"
 * )
 */
class SearchApiField extends SearchApiBaseFacetSource implements SearchApiFacetSourceInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|null
   */
  protected $configFactory;

  /**
   * The current path stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager|null
   */
  protected $entityTypeManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager|null
   */
  protected $typedDataManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, $query_type_plugin_manager, $search_results_cache, RequestStack $request_stack, CurrentPathStack $current_path_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $query_type_plugin_manager, $search_results_cache);

    $this->currentPathStack = $current_path_stack;
    $this->requestStack = $request_stack;

    // Load facet plugin definition and depending on those settings; load the
    // corresponding search api page and load its index.
    $field_id = $plugin_definition['search_api_field'];
    /* @var $page \Drupal\search_api\Utility\QueryHelper */
    $field = FieldStorageConfig::load($field_id);
    $index = $field->getSetting('index');
    $this->index = Index::load($index);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.facets.query_type'),
      $container->get('search_api.query_helper'),
      $container->get('request_stack'),
      $container->get('path.current')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fillFacetsWithResults(array $facets) {
    $plugin_definition_id = $this->getPluginDefinition()['display_id'];
    // Check if there are results in the static cache.
    $results = $this->searchApiQueryHelper->getResults($plugin_definition_id);

    // If there are no results, execute the search page and check for results
    // again. This happens when a page or block is cached, so Search API has
    // not fired an actual search.
    if (!$results) {
      /* @var $search_api_index \Drupal\search_api\IndexInterface */
      $search_api_index = $this->getIndex();

      // Create the query.
      $options = [
        'parse_mode' => 'direct',
        // @Todo Fix limit, get it from field settings.
        'limit' => 10,
        'offset' => isset($_GET['page']) ? $_GET['page'] : 0,
      ];
      $query = $search_api_index->query($options);
      $query->setSearchId($plugin_definition_id);

      // Keys.
      $keys = $this->requestStack->getCurrentRequest()->get('keys');
      if (!empty($keys)) {
        $query->keys($keys);
      }

      // Index fields.
      $query->setFulltextFields();

      // Execute the query.
      $results = $query->execute();
    }

    // If we got results from the cache, this is the first code executed in this
    // method, so it's good to double check that we can actually work with
    // $results.
    if ($results instanceof ResultSetInterface) {
      // Get our facet data from the results.
      $facet_results = $results->getExtraData('search_api_facets');

      // Loop over each facet and execute the build method from the given query
      // type.
      foreach ($facets as $facet) {
        if (isset($facet_results[$facet->getFieldIdentifier()])) {
          $configuration = [
            'query' => NULL,
            'facet' => $facet,
            'results' => $facet_results[$facet->getFieldIdentifier()],
          ];

          // Get the Facet Specific Query Type so we can process the results
          // using the build() function of the query type.
          $query_type = $this->queryTypePluginManager->createInstance($facet->getQueryType(), $configuration);
          $query_type->build();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return Url::createFromRequest($this->requestStack->getCurrentRequest());
  }

  /**
   * {@inheritdoc}
   */
  public function isRenderedInCurrentRequest() {
    // @todo Find out how this works.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndex() {
    return $this->index;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
