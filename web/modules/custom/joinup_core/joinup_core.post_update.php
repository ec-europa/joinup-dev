<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

/**
 * Fix the last updated time of node entities.
 */
function joinup_core_post_update_0106300(&$sandbox) {
  // In Joinup, all node updates through the UI always create a new revision.
  // Only updates through the API can update an entity without creating a new
  // revision. However, after moving the visit_count outside the storage, there
  // is no other functionality that can perform such a task.
  // Thus, it is safe to assume, that the "changed" property of each revision is
  // the same as the "revision_timestamp". The following query will fix all
  // cases where the entity was updated by an automatic procedure that wasn't
  // actually touching any values.
  $query = <<<QUERY
UPDATE {node_field_revision} nfr
INNER JOIN {node_revision} nr ON nfr.vid = nr.vid
SET nfr.changed = nr.revision_timestamp
WHERE nfr.changed != nr.revision_timestamp
QUERY;

  \Drupal::database()->query($query);
  $query = <<<QUERY
UPDATE {node_field_data} nfd
INNER JOIN {node_field_revision} nfr ON nfd.vid = nfr.vid
SET nfd.changed = nfr.changed
QUERY;

  \Drupal::database()->query($query);
}
