<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

/**
 * Fix the EIF recommendation menu link route.
 */
function joinup_core_post_update_0106600(): void {
  \Drupal::entityTypeManager()->getStorage('menu_link_content')->load(11390)
    ->set('link', 'route:view.eif_recommendation.all;rdf_entity=http_e_f_fdata_ceuropa_ceu_fw21_f405d8980_b3f06_b4494_bb34a_b46c388a38651')
    ->save();
}
