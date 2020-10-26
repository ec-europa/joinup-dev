<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

/**
 * Stores various options related to the creation of group content.
 */
final class ContentCreationOptions {

  /**
   * Option defining that Only facilitators and authors can create content.
   */
  const FACILITATORS_AND_AUTHORS = 'facilitators_and_authors';

  /**
   * Option defining that members and facilitators can create content.
   */
  const MEMBERS = 'only_members';

  /**
   * Option defining that any registered user can create content.
   */
  const REGISTERED_USERS = 'any_user';

}
