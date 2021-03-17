<?php

/**
 * @file
 * Post update functions for Joinup.
 *
 * This should only contain update functions that rely on the Drupal API but
 * need to run _before_ the configuration is imported.
 *
 * For example this can be used to enable a new module that needs to have its
 * code available for the configuration to be successfully imported or updated.
 *
 * In most cases though update code should be placed in joinup_core.deploy.php.
 */

declare(strict_types = 1);

/**
 * Moves the data about the content listing of custom pages to paragraphs (1).
 */
function joinup_core_post_update_0106900(array &$sandbox): void {
  $db = \Drupal::database();
  $query = <<<Query
SELECT cl.entity_id AS nid, cl.field_cp_content_listing_value AS listing
FROM {node__field_cp_content_listing} cl
INNER JOIN {node_field_data} n ON cl.entity_id  = n.nid
ORDER BY nid
Query;
  $items = $db->query($query)->fetchAll();

  $items = array_filter(
    array_map(
      function (\stdClass $item): array {
        $item->listing = unserialize($item->listing);
        $item->listing['fields']['field_content_listing_type'] = $item->listing['fields']['field_cp_content_listing_content_type'];
        unset($item->listing['fields']['field_cp_content_listing_content_type']);
        return (array) $item;
      },
      $items
    ),
    function (array $item): bool {
      // Skip if there is the field is not enabled and there are no query
      // presets, meaning that the field is not simply disabled.
      return $item['listing']['enabled'] != 0 || !empty($item['listing']['query_presets']);
    }
  );

  \Drupal::state()->set('isaicp_5880', $items);
  $db->truncate('node__field_cp_content_listing');
  $db->truncate('node_revision__field_cp_content_listing');
}
