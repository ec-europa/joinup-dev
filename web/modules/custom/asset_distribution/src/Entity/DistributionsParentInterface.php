<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution\Entity;

/**
 * Bundle class for content having distributions as children.
 */
interface DistributionsParentInterface {

  /**
   * Returns the child distribution IDs.
   *
   * @return string[]
   *   The child distribution IDs.
   */
  public function getDistributionIds(): array;

}
