<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\redirect\Entity\Redirect;

/**
 * Update aliases for entities with the old alias.
 */
function joinup_core_post_update_0106401(&$sandbox): string {
  $rdf_storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
  if (empty($sandbox['entity_ids'])) {
    $sandbox['entity_ids'] = $rdf_storage->getQuery()->execute();
    $sandbox['count'] = 0;
    $sandbox['updated'] = 0;
    $sandbox['redirects'] = 0;
    $sandbox['max'] = count($sandbox['entity_ids']);
  }

  $entity_ids = array_splice($sandbox['entity_ids'], 0, 50);

  $alias_manager = \Drupal::getContainer()->get('path_alias.manager');
  $alias_generator = \Drupal::getContainer()->get('pathauto.generator');
  $redirect_storage = \Drupal::entityTypeManager()->getStorage('redirect');
  foreach ($rdf_storage->loadMultiple($entity_ids) as $entity) {
    // Update aliases for the entity's default language and its translations.
    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $old_alias = $alias_manager->getAliasByPath($entity->toUrl()->toString());
      /** @var \Drupal\Core\Entity\TranslatableInterface $translated_entity */
      $translated_entity = $entity->getTranslation($langcode);
      $result = $alias_generator->createEntityAlias($translated_entity, 'bulkupdate');
      if ($result) {
        $sandbox['updated']++;
        if ($old_alias === $result['alias']) {
          continue;
        }

        $redirects = $redirect_storage->loadByProperties([
          'redirect_source__path' => $old_alias,
          'redirect_redirect__uri' => 'internal:' . $result['alias'],
          'language' => $translated_entity->language(),
        ]);
        if (empty($redirects)) {
          Redirect::create([
            'redirect_source' => $old_alias,
            'redirect_redirect' => 'internal:' . $result['alias'],
            'language' => $translated_entity->language(),
            'status_code' => '301',
          ])->save();
          $sandbox['redirects']++;
        }
      }
    }
  }

  $sandbox['count'] += count($entity_ids);
  $sandbox['#finished'] = (int) empty($sandbox['entity_ids']);
  return "Processed {$sandbox['count']}/{$sandbox['max']}. {$sandbox['updated']} were updated. {$sandbox['redirects']} redirects were created.";
}
