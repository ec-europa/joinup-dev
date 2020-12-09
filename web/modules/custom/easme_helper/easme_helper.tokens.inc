<?php

/**
 * @file
 * Builds placeholder replacement tokens for EASME related data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function easme_helper_token_info() {
  $type = [
    'name' => t('EASME'),
    'description' => t('Tokens related to EASME.'),
  ];

  $tokens['notifications-site-name'] = [
    'name' => t('Notfications: site name'),
    'description' => t('The site name defined for the email notifications'),
  ];

  return [
    'types' => ['easme' => $type],
    'tokens' => ['easme' => $tokens],
  ];
}

/**
 * Implements hook_tokens().
 */
function easme_helper_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $replacements = [];

  if ($type == 'easme') {
    $easme_helper_config = \Drupal::config('easme_helper.settings');

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the node.
        case 'notifications-site-name':
          $replacements[$original] = $easme_helper_config->get('notifications.site_name');
          break;
      }
    }
  }

  return $replacements;
}
