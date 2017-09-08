<?php

namespace Drupal\joinup_search_arbitrary_facet\Plugin\facets\widget;

use Drupal\facets\FacetInterface;
use Drupal\facets\Widget\WidgetPluginBase;

/**
 * Widget to create facets based on arbitrary queries.
 *
 * @FacetsWidget(
 *   id = "arbitrary_query_facet",
 *   label = @Translation("Arbitrary query facet"),
 *   description = @Translation("A widget that shows a facet based on arbitrary queries."),
 * )
 */
class ArbitraryQueryFacet extends WidgetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    // @todo Implement conversion between machine names and labels.
    $build = parent::build($facet);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType(array $query_types) {
    return $query_types['facet_query'];
  }

}
