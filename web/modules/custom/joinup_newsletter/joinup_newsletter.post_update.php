<?php

/**
 * @file
 * Post update functions for the Joinup Newsletter module.
 */

declare(strict_types = 1);

/**
 * Enable the "OpenEuropa Newsroom Newsletter" module.
 */
function joinup_newsletter_post_update_install_oe_newsroom_newsletter(): void {
  \Drupal::service('module_installer')->install(['oe_newsroom_newsletter']);
}
