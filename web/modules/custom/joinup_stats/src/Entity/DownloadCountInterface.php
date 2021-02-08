<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats\Entity;

/**
 * Interface for meta entities that store download counts.
 */
interface DownloadCountInterface extends StatisticInterface {

  /**
   * Returns the download count.
   *
   * @return int
   *   The number of tracked downloads.
   */
  public function getDownloadCount(): int;

}
