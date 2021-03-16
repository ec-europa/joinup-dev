<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

use Drupal\joinup_group\Entity\GroupInterface;

/**
 * Provides and interface for 'joinup_group.url_alias_updater' service.
 */
interface JoinupGroupContentUrlAliasUpdaterInterface {

  /**
   * Queues the group content for URL alias update.
   *
   * @param \Drupal\joinup_group\Entity\GroupInterface $group
   *   The group whose content will update their URL alias.
   */
  public function queueGroupContent(GroupInterface $group): void;

}
