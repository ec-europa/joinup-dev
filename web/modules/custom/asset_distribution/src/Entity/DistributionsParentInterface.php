<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Bundle class for content having distributions as children.
 */
interface DistributionsParentInterface extends ContentEntityInterface {

  /**
   * Returns the child distribution IDs.
   *
   * @return string[]
   *   The child distribution IDs.
   */
  public function getDistributionIds(): array;

}
