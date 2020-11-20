<?php

declare(strict_types = 1);

namespace Drupal\joinup_search\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\SortProcessorPluginBase;
use Drupal\facets\Result\Result;

/**
 * A processor that orders content types according to a fixed order.
 *
 * @FacetsProcessor(
 *   id = "joinup_search_content_type_fixed_order",
 *   label = @Translation("Fixed ordering for content types"),
 *   description = @Translation("Sorts the facets according to a predefined order. This sort is only applicable for the content type facets."),
 *   stages = {
 *     "sort" = 60
 *   }
 * )
 */
class ContentTypeFixedWidgetOrderProcessor extends SortProcessorPluginBase {

  /**
   * The order in which the facet results should be shown.
   */
  protected const CONTENT_TYPE_ORDER = [
    'collection' => 0,
    'solution' => 1,
    'news' => 2,
    'event' => 3,
    'document' => 4,
    'discussion' => 5,
    'asset_distribution' => 6,
    'asset_release' => 7,
    'newsletter' => 8,
    'custom_page' => 9,
    'licence' => 10,
    'video' => 11,
  ];

  /**
   * {@inheritdoc}
   */
  public function sortResults(Result $a, Result $b): int {
    return $this->getWeight($a) <=> $this->getWeight($b);
  }

  /**
   * Returns the weight of the given facet result.
   *
   * @param \Drupal\facets\Result\Result $result
   *   The fact result.
   *
   * @return int
   *   The weight according to the fixed ordering table, or 1000 if the weight
   *   has not been decided.
   */
  protected function getWeight(Result $result): int {
    return self::CONTENT_TYPE_ORDER[$result->getRawValue()] ?? 1000;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFacet(FacetInterface $facet): bool {
    // This is tailor made to the 'Content type' facet, it does not support
    // anything else.
    return $facet->id() === 'type';
  }

}
