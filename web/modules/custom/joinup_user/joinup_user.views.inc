<?php

/**
 * @file
 * Provide views data for user.module.
 */

declare(strict_types = 1);

/**
 * Implements hook_views_data_alter().
 */
function joinup_user_views_data_alter(array &$data) {
  // Replace the standard views bulk form for users with our customized version.
  $data['users']['user_bulk_form']['field']['id'] = 'joinup_user_bulk_form';
}
