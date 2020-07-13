<?php

declare(strict_types = 1);

namespace Drupal\joinup_user\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Allows filtering on the tristate account status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("user_tristate_status")
 */
class JoinupUserStatus extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions(): ?array {
    return $this->valueOptions = [
      1 => $this->t('Active'),
      0 => $this->t('Blocked'),
      -1 => $this->t('Cancelled'),
    ];
  }

}
