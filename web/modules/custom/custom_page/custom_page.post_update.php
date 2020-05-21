<?php

/**
 * @file
 * Post update functions for the custom_page module.
 */

declare(strict_types = 1);

use Drupal\paragraphs\Entity\Paragraph;

/**
 * Migrate the body field to the new paragraphs field for custom pages.
 */
function custom_page_post_update_0106000(&$sandbox) {
  if (!isset($sandbox['nids'])) {
    $sandbox['nids'] = \Drupal::database()->query("SELECT nid FROM {node_field_data} n WHERE n.type = 'custom_page';")->fetchCol();
    $sandbox['count'] = 0;
    $sandbox['max'] = count($sandbox['nids']);
  }

  $limit = 10;
  $nids = array_splice($sandbox['nids'], 0, $limit);
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  foreach ($node_storage->loadMultiple($nids) as $custom_page) {
    $paragraph = Paragraph::create([
      'type' => 'simple_paragraph',
    ]);
    $paragraph->set('field_body', $custom_page->get('body')->getValue());
    $paragraph->save();
    $custom_page->set('field_paragraphs_body', $paragraph);
    $custom_page->save();
    $sandbox['count']++;
  }

  $sandbox['#finished'] = (int) empty($sandbox['nids']);
  return "Processed {$sandbox['count']} items out of {$sandbox['max']}.";
}
