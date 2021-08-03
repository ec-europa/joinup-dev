<?php

declare(strict_types = 1);

namespace Drupal\joinup_collection;

use Drupal\Core\Site\Settings;

/**
 * Helper class for the Joinup Community module.
 */
class JoinupCommunityHelper {

  /**
   * The default entity ID of the Joinup collection.
   */
  const JOINUP_COLLECTION_DEFAULT_ENTITY_ID = 'http://data.europa.eu/w21/df34e3a2-207b-4910-a804-344931654e20';

  /**
   * Returns the entity ID of the Joinup collection.
   *
   * This will return the collection ID that is defined in `settings.php`, or a
   * fallback default ID if not defined in the settings.
   *
   * @return string
   *   The entity ID.
   */
  public static function getCommunityId(): string {
    return Settings::get('joinup_collection.collection_id', static::JOINUP_COLLECTION_DEFAULT_ENTITY_ID);
  }

}
