<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats\Entity;

/**
 * Bundle class for meta entities that track download counts.
 */
class DownloadCount extends StatisticBase implements DownloadCountInterface {

  /**
   * {@inheritdoc}
   */
  public function getDownloadCount(): int {
    return (int) $this->count->value;
  }

}
