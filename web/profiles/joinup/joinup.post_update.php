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
