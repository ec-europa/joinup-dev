<?php

/**
 * @file
 * Includes custom settings for the UAT environment.
 */

/**
 * Override the default sparql connection class.
 *
 * @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4206
 * @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4637
 */
$databases['sparql_default']['default']['namespace'] = "Drupal\\Driver\\Database\\joinup_sparql";
