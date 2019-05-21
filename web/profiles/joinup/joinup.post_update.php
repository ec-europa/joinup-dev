<?php

/**
 * @file
 * Post update functions for the Joinup profile.
 */

declare(strict_types = 1);

/**
 * Enable the "Views data export" module.
 */
function joinup_post_update_install_views_data_export(): void {
  \Drupal::service('module_installer')->install(['views_data_export']);
}

/**
 * Enable modules related to geocoding.
 */
function joinup_post_update_install_geocoder(): void {
  $modules = [
    'geocoder',
    'geocoder_geofield',
    'geocoder_field',
    'geofield',
    'oe_webtools_geocoding',
    'oe_webtools_maps',
  ];
  \Drupal::service('module_installer')->install($modules);
}

/**
 * Enable the "Joinup RSS" module.
 */
function joinup_post_update_install_joinup_rss() {
  \Drupal::service('module_installer')->install(['joinup_rss']);
}

/**
 * Enable the "ISA2 Analytics" module.
 */
function joinup_post_update_install_isa2_analytics() {
  \Drupal::service('module_installer')->install(['isa2_analytics']);
}

/**
 * Enable the "config_readonly" module.
 */
function joinup_post_update_install_config_readonly() {
  \Drupal::service('module_installer')->install(['config_readonly']);
}
