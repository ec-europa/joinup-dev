<?php

/**
 * @file
 * Post update functions for the Joinup front page module.
 */

declare(strict_types = 1);

use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Adds site-wide pinned entities to the front page menu.
 */
function joinup_front_page_post_update_assign_menu_pinned_values(): string {
  // This runs as a post update because the front page menu needs to be imported
  // into active configuration in order to assign the menu items. We cannot
  // include this logic in joinup_front_page_install() because the module will
  // get enabled during the config updates. We need to run this after all config
  // of modules that have a `field_site_pinned` field has been imported.
  $updated = [];

  /** @var \Drupal\joinup_front_page\FrontPageMenuHelperInterface $front_page_helper */
  $front_page_helper = \Drupal::service('joinup_front_page.front_page_helper');
  foreach (['node', 'rdf_entity'] as $type) {
    try {
      $storage = \Drupal::entityTypeManager()->getStorage($type);
      $ids = $storage->getQuery()->condition('field_site_pinned', 1)->execute();
      foreach ($storage->loadMultiple($ids) as $entity) {
        if (empty($front_page_helper->getFrontPageMenuItem($entity))) {
          $front_page_helper->pinSiteWide($entity);
          $updated[] = $entity->label();
        }
      }
    }
    catch (PluginNotFoundException $e) {
      // Skip the update if one of the entity types is not defined in the
      // current installation. This is not expected to occur since both entity
      // types are enabled in production, but this allows us to execute this
      // code without having to add dependencies on the `node` and `rdf_entity`
      // modules. Since this is intended to run only once we want to avoid
      // introducing hard dependencies for the entire lifetime of the project.
    }
  }

  return 'Pinned to front page: ' . implode(', ', $updated);
}
