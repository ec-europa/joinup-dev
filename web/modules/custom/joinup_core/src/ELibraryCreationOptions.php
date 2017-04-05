<?php

namespace Drupal\joinup_core;

/**
 * Stores various options related to the creation of eLibrary content.
 */
final class ELibraryCreationOptions {

  /**
   * Default option.
   */
  // @codingStandardsIgnoreLine
  const __default = self::FACILITATORS;

  /**
   * Elibrary option defining that only facilitators can create content.
   */
  const FACILITATORS = 0;

  /**
   * Elibrary option defining that members and facilitators can create content.
   */
  const MEMBERS = 1;

  /**
   * Elibrary option defining that any registered user can create content.
   */
  const REGISTERED_USERS = 2;

}
