<?php

/**
 * @file
 * Post update functions for Joinup.
 *
 * This should only contain update functions that rely on the Drupal API but
 * need to run _before_ the configuration is imported.
 *
 * For example this can be used to enable a new module that needs to have its
 * code available for the configuration to be successfully imported or updated.
 *
 * In most cases though update code should be placed in joinup_core.deploy.php.
 */

declare(strict_types = 1);

/**
 * Update existing custom pages if no filters or query is set.
 */
function joinup_core_post_update_0107400(&$sandbox): void {
  $nids = \Drupal::entityQuery('node')->condition('type', 'custom_page')->execute();
  foreach ($nids as $nid) {
    /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
    $entity = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    if ($entity->hasField('field_paragraphs_body')) {
      $elements = $entity->get('field_paragraphs_body');
      for ($i = 0; $i < $elements->count(); $i++) {
        $paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->load($elements->get($i)->target_id);
        if ($paragraph->bundle() == 'content_listing') {
          $values = $paragraph->field_content_listing->value;
          if (empty($values['query_presets']) && !array_key_exists('query_builder', $values)) {
            $elements->removeItem($i);
            // Caution: decrement the counter as removeItem()
            // also does a rekey().
            $i--;
          }
        }
      }
      $entity->save();
    }
  }
}
