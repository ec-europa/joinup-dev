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
    if ($this->view->id() === 'eif_recommendation') {
      // Avoid any filtering on group for 'eif_recommendation' view.
      return;
    }
    parent::query($group_by);
  }

}
