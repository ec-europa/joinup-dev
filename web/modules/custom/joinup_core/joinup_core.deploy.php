<?php

/**
 * @file
 * Deploy functions for Joinup.
 *
 * This should only contain update functions that rely on the Drupal API and
 * need to run _after_ the configuration is imported.
 *
 * This is applicable in most cases. However in case the update code enables
 * some functionality that is required for configuration to be successfully
 * imported, it should instead be placed in joinup_core.post_update.php.
 */

declare(strict_types = 1);

use Drupal\Core\Batch\Percentage;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\honeypot\ExpiredRecordsDeleter;

/**
 * Delete expired Honeypot records.
 */
function joinup_core_deploy_0106700(&$sandbox): TranslatableMarkup {
  // Initialize the sandbox and results counter on first run.
  if (!isset($sandbox['total'])) {
    $sandbox['total'] = ExpiredRecordsDeleter::getExpiredRecordCount();
  }
  if (!isset($sandbox['current'])) {
    $sandbox['current'] = 0;
  }

  if ($sandbox['total'] == 0) {
    $sandbox['#finished'] = 1;
    return t('There are no expired records to delete.');
  }

  $database = \Drupal::database();
  $query = $database->select('key_value_expire', 'kve');
  $query->fields('kve', ['name']);
  $query->condition('collection', 'honeypot_time_restriction');
  $query->condition('expire', 2147483647);
  $query->range(0, 50000);
  $result = $query->execute();

  $deleted_count = $database->delete('key_value_expire')
    ->condition('collection', 'honeypot_time_restriction')
    ->condition('name', $result->fetchCol(), 'IN')
    ->execute();

  if ($deleted_count == 0) {
    $sandbox['#finished'] = 1;
    return t('All expired records have been deleted.');
  }

  $sandbox['current'] += $deleted_count;
  $sandbox['#finished'] = $sandbox['current'] / $sandbox['total'];
  return t('Deleted @deleted of @total records (@percentage% complete)', [
    '@deleted' => $sandbox['current'],
    '@total' => $sandbox['total'],
    '@percentage' => Percentage::format($sandbox['total'], $sandbox['current']),
  ]);
}

/**
 * Verify site in Google search console.
 */
function joinup_core_deploy_0106701(&$sandbox): TranslatableMarkup {
  \Drupal::database()->insert('site_verify')
    ->fields([
      'engine' => 'google',
      'file' => 'google49574b354ced8b03.html',
      'file_contents' => 'google-site-verification: google49574b354ced8b03.html',
      'meta' => '',
    ])
    ->execute();
  return t('Google site verification through file has been set.');
}
