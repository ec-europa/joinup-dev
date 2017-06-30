<?php

/**
 * @file
 * Drush commands related to joinup.
 */

/**
 * Implements hook_drush_command().
 */
function joinup_core_drush_command() {
  $items = [];

  $items['joinup-status-check'] = [
    'description' => 'Checks for status errors according to requirements.',
    'examples' => [
      'drush joinup-status-check' => dt('Check for requirement errors.'),
    ],
  ];

  return $items;
}

/**
 * Checks for requirement errors.
 */
function drush_joinup_core_joinup_status_check() {
  /** @var \Drupal\system\SystemManager $system_manager */
  $system_manager = \Drupal::service('system.manager');
  $requirements = $system_manager->listRequirements();
  $requirements = array_filter($requirements, function ($requirement) {
    return $requirement['severity'] === REQUIREMENT_ERROR;
  });
  if (!empty($requirements)) {
    foreach ($requirements as $requirement) {
      drush_set_error($requirements['title'], drush_render($requirement['description']));
    }
  }
}
