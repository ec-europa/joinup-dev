<?php

/**
 * @file
 * Builds placeholder replacement tokens for community content related data.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;

/**
 * Implements hook_token_info_alter().
 */
function joinup_community_content_token_info_alter(&$info) {
  $info['tokens']['node']['diff-url-latest'] = [
    'name' => t('Diff from previous revision'),
    'description' => t('A link to the revision comparison page between the last two revisions of the content.'),
  ];
}

/**
 * Implements hook_tokens().
 */
function joinup_community_content_tokens($type, array $tokens, array $data = [], array $options = [], BubbleableMetadata $bubbleable_metadata = NULL) {
  $replacements = [];

  if ($type == 'node' && !empty($data['node'])) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'diff-url-latest':
          // A link to the latest diff can be rendered only if we have at least
          // two revisions.
          /** @var \Drupal\node\NodeStorageInterface $node_storage */
          $node_storage = \Drupal::entityTypeManager()->getStorage('node');
          $revision_ids = $node_storage->revisionIds($node);
          if (count($revision_ids) > 1) {
            list($left_revision, $right_revision) = array_slice($revision_ids, -2, 2);

            $replacements[$original] = Url::fromRoute('diff.revisions_diff', [
              'node' => $node->id(),
              'left_revision' => $left_revision,
              'right_revision' => $right_revision,
              'filter' => \Drupal::service('plugin.manager.diff.layout')->getDefaultLayout(),
            ])->setAbsolute()->toString();
          }
      }
    }
  }

  return $replacements;
}
