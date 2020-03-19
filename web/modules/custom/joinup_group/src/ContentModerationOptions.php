<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

/**
 * Stores various options related to the moderation of group content.
 */
final class ContentModerationOptions {

  /**
   * Option defining that anyone can publish directly to the group.
   */
  const NO_MODERATION = 'no_moderation';

  /**
   * Option defining that facilitator content are not moderated.
   */
  const MEMBERS_MODERATION = 'members_moderation';

  /**
   * Option defining that all content are moderated.
   */
  const ALL_MODERATION = 'all_moderation';

}
