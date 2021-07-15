<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Entity;

/**
 * Interface for entities that can be pinned in groups.
 *
 * Collection and solution facilitators have the ability to "pin" selected
 * entities in their groups. A pinned entity will be prominently displayed in
 * the group homepage, giving it more visibility. Pinned items are shown first
 * and have a visual indication (a pin icon) to signify their importance to the
 * viewer.
 *
 * Currently the following bundles can be pinned:
 * - Solutions: can be pinned in all the collections they are affiliated with.
 * - Community content: can be pinned only in their own solution of collection.
 *
 * Other group content (such as custom pages) cannot be pinned.
 */
interface PinnableGroupContentInterface extends GroupContentInterface {

  /**
   * Checks if the entity is pinned inside any group or a specific one.
   *
   * @param \Drupal\joinup_group\Entity\GroupInterface|null $group
   *   The group in which the entity could be pinned. When omitted this will
   *   check if the entity is pinned in any group.
   *
   * @return bool
   *   TRUE if the entity is pinned, FALSE otherwise.
   */
  public function isPinned(?GroupInterface $group = NULL): bool;

  /**
   * Pins the entity in the given group.
   *
   * @param \Drupal\joinup_group\Entity\GroupInterface|null $group
   *   The group in which the entity will be pinned. If omitted, it will be
   *   pinned in the entity's parent group.
   *
   * @return self
   *   The pinned entity, for chaining.
   */
  public function pin(?GroupInterface $group = NULL): PinnableGroupContentInterface;

  /**
   * Unpins the entity from the given group.
   *
   * @param \Drupal\joinup_group\Entity\GroupInterface|null $group
   *   The group from which the entity will be unpinned. If omitted, it will be
   *   unpinned from the entity's parent group.
   *
   * @return self
   *   The unpinned entity, for chaining.
   */
  public function unpin(?GroupInterface $group = NULL): PinnableGroupContentInterface;

  /**
   * Retrieves a list of groups where the entity is pinned.
   *
   * @return string[]
   *   A list of group IDs in which the content is pinned. Since all groups in
   *   Joinup are RDF entities, these are RDF entity IDs.
   */
  public function getPinnedGroupIds(): array;

  /**
   * Returns a list of groups the given entity can be pinned in.
   *
   * Community content can only be pinned in their parent collection or
   * solution. Solutions can be pinned in any of their affiliated collections.
   *
   * @return \Drupal\joinup_group\Entity\GroupInterface[]
   *   A list of groups the entity can be pinned in, keyed by group ID.
   */
  public function getPinnableGroups(): array;

  /**
   * Returns a list of IDs of groups the given entity can be pinned in.
   *
   * Community content can only be pinned in their parent collection or
   * solution. Solutions can be pinned in any of their affiliated collections.
   *
   * @return string[]
   *   A list of IDs of groups the entity can be pinned in.
   */
  public function getPinnableGroupIds(): array;

}
