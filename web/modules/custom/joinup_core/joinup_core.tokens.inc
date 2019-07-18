<?php

/**
 * @file
 * Tokens support for Joinup Core module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function joinup_core_token_info() {
  return [
    'tokens' => [
      'random' => [
        'uuid' => [
          'name' => t('UUID'),
          'description' => t('Generates a universally unique identifier.'),
        ],
      ],
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function joinup_core_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  switch ($type) {
    case 'random':
      /** @var \Drupal\Component\Uuid\UuidInterface $uuid_service */
      $uuid_service = \Drupal::service('uuid');
      foreach ($tokens as $name => $original) {
        if ($name === 'uuid') {
          $replacements[$original] = $uuid_service->generate();
        }
      }
      break;
  }

  return $replacements;
}
