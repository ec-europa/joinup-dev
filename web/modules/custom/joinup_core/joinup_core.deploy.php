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

/**
 * Update the Licence legal type vocabulary.
 */
function joinup_core_deploy_0107300(array &$sandbox): void {
  \Drupal::service('joinup_rdf.vocabulary_fixtures.helper')->importFixtures('licence-legal-type', TRUE);
}
