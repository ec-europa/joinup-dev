<?php

namespace Drupal\search_api_field\Plugin\facets\widget;

use Drupal\facets\FacetInterface;
use Drupal\facets\Result\ResultInterface;
use Drupal\facets\Widget\WidgetPluginBase;

/**
 * The links widget.
 *
 * @FacetsWidget(
 *   id = "type",
 *   label = @Translation("Render the type facet"),
 *   description = @Translation("A simple widget that shows a list of links with icons"),
 * )
 */
class TypeWidget extends WidgetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['soft_limit' => 0] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $build = parent::build($facet);
    $build['#theme'] = 'facets_type_wrapper';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType($query_types) {
    return $query_types['string'];
  }

  /**
   * {@inheritdoc}
   */
  protected function buildResultItem(ResultInterface $result) {
    $count = $result->getCount();
    return [
      '#theme' => $result->isActive() ? 'facets_type_result_item_active' : 'facets_type_result_item',
      '#value' => $result->getDisplayValue(),
      '#show_count' => $this->getConfiguration()['show_numbers'] && ($count !== NULL),
      '#count' => $count,
      '#type' => $result->getRawValue(),
    ];
  }

  /**
   * Builds a renderable array of result items.
   *
   * @param \Drupal\facets\Result\ResultInterface $result
   *   A result item.
   *
   * @return array
   *   A renderable array of the result.
   */
  protected function buildListItems(ResultInterface $result) {
    $classes = ['facet-item'];
    if ($children = $result->getChildren()) {
      $items = $this->prepareLink($result);

      $children_markup = [];
      foreach ($children as $child) {
        $children_markup[] = $this->buildChild($child);
      }

      $classes[] = 'expanded';
      $items['children'] = [$children_markup];

      if ($result->isActive()) {
        $items['#attributes'] = ['class' => ['active-trail']];
      }
    }
    else {
      $items = $this->prepareLink($result);

      if ($result->isActive()) {
        $items['#attributes'] = ['class' => ['is-active']];
      }
    }

    $items['#wrapper_attributes'] = ['class' => $classes];
    $items['#attributes']['data-drupal-facet-item-id'] = $this->facet->getUrlAlias() . '-' . $result->getRawValue();
    $items['#attributes']['class'][] = 'tab--content-type';
    return $items;
  }

}
