<?php

/**
 * @file
 * Views integration.
 */

/**
 * Implements hook_views_data().
 */
function joinup_user_views_data() {
  $data['users']['joinup_authorship'] = [
    'field' => [
      'title' => t('Authorship'),
      'help' => t('Shows if the user is content author.'),
      'id' => 'joinup_authorship',
    ],
  ];
  return $data;
}
