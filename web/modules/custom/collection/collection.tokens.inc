<?php

/**
 * @file
 * Token support for the Joinup Collection module.
 */

declare(strict_types = 1);

/**
 * Implements hook_token_info_alter().
 */
function collection_token_info_alter(array &$data): void {
  $data['tokens']['rdf_entity']['collection'] = [
    'name' => t('Collection'),
    'description' => t('The label of the top collection'),
    'needs-data' => 'rdf_entity',
  ];
}
