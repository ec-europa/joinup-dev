<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats\Entity;

/**
 * Interface for entities that track the number of times they have been visited.
 */
interface VisitCountAwareInterface extends StatisticsAwareInterface {

  /**
   * Returns the visit count.
   *
   * @return int
   *   The number of tracked page views.
   */
  public function getVisitCount(): int;

}
