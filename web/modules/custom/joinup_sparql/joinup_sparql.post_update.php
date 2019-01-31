<?php

/**
 * @file
 * Post update functions for Joinup SPARQL module.
 */

declare(strict_types = 1);

/**
 * Install the 'sparql_entity_storage' module.
 */
function joinup_sparql_post_update_install_sparql_entity_storage() {
  \Drupal::service('module_installer')->install(['sparql_entity_storage']);
}
