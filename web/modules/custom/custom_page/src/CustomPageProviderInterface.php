<?php

declare(strict_types = 1);

namespace Drupal\custom_page;

/**
 * Provides custom pages to whoever desires them.
 */
interface CustomPageProviderInterface {

  /**
   * Returns the custom pages that belong to the given group.
   *
   * @param string $group_id
   *   The entity ID of the group for which to return the custom pages.
   * @param bool $include_disabled
   *   Whether or not to include custom pages that are disabled by the group
   *   facilitators and are not visible in the group menu. Defaults to FALSE.
   *
   * @return \Drupal\node\NodeInterface[]
   *   The custom page entities.
   */
  public function getCustomPagesByGroupId(string $group_id, bool $include_disabled = FALSE): array;

}
