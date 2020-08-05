<?php

declare(strict_types = 1);

namespace Drupal\eif\Plugin\views\argument;

use Drupal\joinup_group\Plugin\views\argument\SearchApiGroupArgument;

/**
 * Replacement class for 'search_api_group' plugin.
 *
 * @see \joinup_group_views_data_alter()
 * @see \Drupal\joinup_group\Plugin\views\argument\SearchApiGroupArgument
 */
class EifGroupArgument extends SearchApiGroupArgument {

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $views = ['eif_recommendation', 'eif_solutions'];
    if (in_array($this->view->id(), $views, TRUE)) {
      // Avoid any filtering on group for some views.
      return;
    }
    parent::query($group_by);
  }

}
