<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\Core\Url;
use Drupal\eif\Eif;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\sparql_entity_storage\UriEncoder;

/**
 * Create the solutions page menu link in EIF Toolbox.
 */
function joinup_core_post_update_0106400(array &$sandbox): void {
  MenuLinkContent::create([
    'title' => t('Solutions'),
    'menu_name' => 'ogmenu-3444',
    'link' => [
      'uri' => Url::fromRoute('view.eif_solutions.page', [
        'rdf_entity' => UriEncoder::encodeUrl(Eif::EIF_ID),
      ])->toUriString(),
    ],
    'weight' => 5,
  ])->save();
}
