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
 * Fix the EIF recommendation menu link route.
 */
function joinup_core_post_update_0106700(): void {
  \Drupal::entityTypeManager()->getStorage('menu_link_content')->load(11390)
    ->set('link', 'route:view.eif_recommendation.all;rdf_entity=http_e_f_fdata_ceuropa_ceu_fw21_f405d8980_b3f06_b4494_bb34a_b46c388a38651')
    ->save();
}
