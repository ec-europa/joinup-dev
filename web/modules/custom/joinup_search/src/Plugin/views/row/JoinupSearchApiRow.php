<?php

declare(strict_types = 1);

namespace Drupal\joinup_seach\Plugin\views\row;

use Drupal\search_api\Plugin\views\row\SearchApiRow;
use Drupal\search_api\SearchApiException;

/**
 * Provides a row plugin for displaying a result as a rendered item.
 *
 * @ViewsRow(
 *   id = "joinup_search_api",
 *   title = @Translation("Rendered entity"),
 *   help = @Translation("Displays entity of the matching search API item"),
 * )
 *
 * @see joinup_search_views_plugins_row_alter()
 */
class JoinupSearchApiRow extends SearchApiRow {

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    parent::render($row);

    $datasource_id = $row->search_api_datasource;
    $view_mode = 'search_result_featured';
    try {
      return $this->index->getDatasource($datasource_id)->viewItem($row->_object, $view_mode);
    }
    catch (SearchApiException $e) {
      $this->logException($e);
      return '';
    }
  }

}
