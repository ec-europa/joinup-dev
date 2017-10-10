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
    'types' => [
      'joinup-current-page' => [
        'name' => t('Joinup current page'),
        'description' => t('Custom tokens that can be derived from the context of the current page.'),
      ],
    ],
    'tokens' => [
      'joinup-current-page' => [
        'group-context' => [
          'name' => t('Group context'),
          'description' => t('The collection or solution a page belongs to.'),
        ],
        'page-type' => [
          'name' => t('Page type'),
          'description' => t('Human readable page type. Intended for analytics.'),
        ],
      ],
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
    case 'joinup-current-page':
      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'group-context':
            /** @var \Drupal\Core\Entity\ContentEntityInterface $group */
            if ($group = \Drupal::service('og.context')->getGroup()) {
              $replacements[$original] = $group->label();
            }
            break;

          case 'page-type':
            $replacements[$original] = \Drupal::service('joinup_core.page_type_determiner')->getType();
            break;
        }
      }
      break;

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
