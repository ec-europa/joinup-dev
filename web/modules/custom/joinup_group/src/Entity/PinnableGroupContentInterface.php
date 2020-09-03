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
 *
 * *Note that this interface is NOT for pinning entities to the frontpage*
 *
 * There is a similarly named concept in Joinup which allows content to be
 * "Pinned to the frontpage" but this is implemented using a menu. The same
 * naming is used since from a UI perspective the actions of "pinning" content
 * to a collection / solution / front page is equivalent.
 *
 * See FrontPageMenuHelper and FrontPageMenuController for front page pinning.
 *
 * @see \Drupal\joinup_front_page\Controller\FrontPageMenuController
 * @see \Drupal\joinup_front_page\FrontPageMenuHelper
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
   * @param \Drupal\joinup_group\Entity\GroupInterface $group
   *   The group in which the entity will be pinned.
   *
   * @return self
   *   The pinned entity, for chaining.
   */
  public function pin(GroupInterface $group): self;

  /**
   * Unpins the entity from the given group.
   *
   * @param \Drupal\joinup_group\Entity\GroupInterface $group
   *   The group from which the entity will be unpinned.
   *
   * @return self
   *   The unpinned entity, for chaining.
   */
  public function unpin(GroupInterface $group): self;

  /**
   * Retrieves a list of groups where the entity is pinned.
   *
   * @return string[]
   *   A list of group IDs in which the content is pinned. Since all groups in
   *   Joinup are RDF entities, these are RDF entity IDs.
   */
  public function getPinnedGroupIds(): array;

}
