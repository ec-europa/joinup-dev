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
 * Backup the field policy domain node field.
 */
function joinup_core_post_update_0107000(&$sandbox) {
  $queries = [
    'ALTER TABLE `node__field_policy_domain` RENAME TO `node__field_topic_backup`;',
    'CREATE TABLE `node__field_policy_domain` LIKE `node__field_topic_backup`;',
    'ALTER TABLE `node_revision__field_policy_domain` RENAME TO `node_revision__field_topic_backup`;',
    'CREATE TABLE `node_revision__field_policy_domain` LIKE `node_revision__field_topic_backup`;',
  ];

  foreach ($queries as $query) {
    \Drupal::database()->query($query);
  }

  \Drupal::getContainer()->get('sparql.endpoint')->query('MOVE <http://policy_domain> TO <http://topic>');
}
