<?php

declare(strict_types = 1);

namespace Drupal\collection\Entity;

use Drupal\joinup_group\Entity\GroupContentTrait;

/**
 * Reusable methods for node collection content.
 */
trait NodeCommunityContentTrait {

  use GroupContentTrait;

  /**
   * {@inheritdoc}
   */
  public function getCommunity(): CommunityInterface {
    $group = $this->getGroup();
    if (!$group instanceof CommunityInterface) {
      return $group->getCommunity();
    }
    /** @var \Drupal\collection\Entity\CommunityInterface $group */
    return $group;
  }

}
