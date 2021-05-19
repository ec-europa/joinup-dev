<?php

namespace Drupal\joinup_group\Plugin\views\filter;

use Drupal\joinup_group\Plugin\views\BundleListTrait;
use Drupal\search_api\Plugin\views\filter\SearchApiOptions;

/**
 * Filters the Search API 'entity_bundle' with a fixed set of possible values.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("joinup_search_api_entity_bundle")
 */
class EntityBundleSearchApiFilter extends SearchApiOptions {

  use BundleListTrait;

  /**
   * {@inheritdoc}
   */
  public function getValueOptions(): ?array {
    if (!isset($this->valueOptions)) {
      $this->valueOptions = $this->getBundleLabels();
    }
    return $this->valueOptions;
  }

}
