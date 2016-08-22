<?php

namespace Drupal\search_api_field\Plugin\facets\facet_source;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\facets\FacetSource\SearchApiFacetSourceInterface;
use Drupal\facets\Plugin\facets\facet_source\SearchApiBaseFacetSource;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Query\ResultSetInterface;

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
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // TODO: Implement calculateDependencies() method.
  }

  use DependencySerializationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager|null
   */
  protected $entityTypeManager;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager|null
   */
  protected $typedDataManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|null
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, $query_type_plugin_manager, $search_results_cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $query_type_plugin_manager, $search_results_cache);

    // Load facet plugin definition and depending on those settings; load the
    // corresponding search api page and load its index.
    $field_id = $plugin_definition['search_api_field'];
    /* @var $page \Drupal\search_api_page\SearchApiPageInterface */
    $field = FieldStorageConfig::load($field_id);
    $index = $field->getSetting('index');
    $this->index = Index::load($index);
  }

  /**
   * {@inheritdoc}
   */
  public function fillFacetsWithResults($facets) {
    // Check if there are results in the static cache.
    $results = $this->searchApiResultsCache->getResults($this->pluginId);

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
        'search id' => $this->pluginId,
      ];
      $query = $search_api_index->query($options);

      // Keys.
      $keys = \Drupal::request()->get('keys');
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
          $configuration = array(
            'query' => NULL,
            'facet' => $facet,
            'results' => $facet_results[$facet->getFieldIdentifier()],
          );

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
    // @todo Is this always right?
    $path = \Drupal::service('path.current')->getPath();
    return $path . \Drupal::request()->get('keys');
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

}
