<?php

/**
 * @file
 * Sync the download count from piwik to Drupal.
 */
use Drupal\field\Entity\FieldConfig;

/**
 * Implements hook_drush_command().
 */
function piwik_download_count_drush_command() {
  $items = [];

  $items['piwik-sync'] = [
    'description' => 'Sync piwik download count to Drupal.',
  ];
  return $items;
}

/**
 * Sync.
 */
function drush_piwik_download_count_piwik_sync() {
  $field_config = array_filter(FieldConfig::loadMultiple(), function (FieldConfig $field_config) {
    return $field_config->getThirdPartySettings('piwik_download_count')['sync'];
  });
  $field_config;
  $a = 1;
}
