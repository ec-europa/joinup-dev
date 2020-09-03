<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Entity;

/**
 * Reusable methods for pinnable group content entities.
 */
trait PinnableGroupContentTrait {

  /**
   * {@inheritdoc}
   */
  public function isPinned(?GroupInterface $group = NULL): bool {
    assert(FALSE, 'Not implemented yet');
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function pin(GroupInterface $group): PinnableGroupContentInterface {
    assert(FALSE, 'Not implemented yet');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unpin(GroupInterface $group): PinnableGroupContentInterface {
    assert(FALSE, 'Not implemented yet');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPinnedGroupIds(): array {
    assert(FALSE, 'Not implemented yet');
    return [];
  }

}
