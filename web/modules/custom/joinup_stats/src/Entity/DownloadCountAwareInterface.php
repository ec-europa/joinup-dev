<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats\Entity;

/**
 * Interface for entities that track how often they have been downloaded.
 */
interface DownloadCountAwareInterface extends StatisticsAwareInterface {

  /**
   * Returns the download count.
   *
   * @return int
   *   The number of tracked downloads.
   */
  public function getDownloadCount(): int;

}
