<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

use Drupal\joinup_group\Entity\GroupInterface;

/**
 * Provides node access grant IDs for groups.
 *
 * Group facilitators have the 'view any unpublished content' permission within
 * their groups. In order to make this work we provide a node access grant for
 * the 'joinup_group_view_unpublished' realm.
 *
 * The node access grants work with numeric grant IDs while our groups have
 * string IDs so we store a mapping in the 'joinup_group_node_access' table.
 * This service provides the mapping data.
 */
interface JoinupGroupNodeAccessGrantsInterface {

  /**
   * Returns the node access grant ID for the given group.
   *
   * @param \Drupal\joinup_group\Entity\GroupInterface $group
   *   The group for which to return the node access grant ID.
   *
   * @return int
   *   The grant ID.
   */
  public function getNodeAccessGrantId(GroupInterface $group): int;

}
