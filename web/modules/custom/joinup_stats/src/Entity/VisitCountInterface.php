<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats\Entity;

/**
 * Interface for meta entities that store visit counts.
 */
interface VisitCountInterface extends StatisticInterface {

  /**
   * Returns the visit count.
   *
   * @return int
   *   The number of tracked page views.
   */
  public function getVisitCount(): int;

}
