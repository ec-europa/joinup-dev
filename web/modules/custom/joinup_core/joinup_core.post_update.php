<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\menu_link_content\Entity\MenuLinkContent as MenuLinkContentEntity;

/**
 * Fix solutions with more than one OG menu instances.
 */
function joinup_core_post_update_0106400(?array &$sandbox = NULL): void {
  $db = \Drupal::database();

  // Broken solutions.
  $ids = [
    'http://administracionelectronica.gob.es/ctt/archive',
    'http://administracionelectronica.gob.es/ctt/eemgde',
    'http://administracionelectronica.gob.es/ctt/documentoe',
    'http://administracionelectronica.gob.es/ctt/dscp',
    'http://administracionelectronica.gob.es/ctt/pau',
    'http://administracionelectronica.gob.es/ctt/pfiaragon',
    'http://administracionelectronica.gob.es/ctt/dir3',
    'http://administracionelectronica.gob.es/ctt/svd',
    'http://administracionelectronica.gob.es/ctt/scsp',
    'http://administracionelectronica.gob.es/ctt/tsa',
    'http://administracionelectronica.gob.es/ctt/afirma',
    'http://administracionelectronica.gob.es/ctt/codice',
    'http://administracionelectronica.gob.es/ctt/badaral',
    'http://administracionelectronica.gob.es/ctt/regfia',
    'http://administracionelectronica.gob.es/ctt/sicres',
  ];

  // By accident, these solutions have two or more OG Menu instance each. Only
  // the latest is valid. Collect the unused OG Menu instance IDs.
  $deleted_ogmenu_ids = [];
  foreach ($ids as $id) {
    $per_entity_ogmenu_ids = $db->query("SELECT entity_id FROM {ogmenu_instance__og_audience} WHERE og_audience_target_id = :id ORDER BY entity_id", [
      ':id' => $id,
    ])->fetchCol();
    // Remove the latest, valid, ID.
    array_pop($per_entity_ogmenu_ids);
    $deleted_ogmenu_ids = array_merge($deleted_ogmenu_ids, $per_entity_ogmenu_ids);
  }
  // Remove redundant OG Menu instances. Their menu links are removed too.
  // @see \Drupal\og_menu\Entity\OgMenuInstance::preDelete()
  $ogmenu_instance_storage = \Drupal::entityTypeManager()->getStorage('ogmenu_instance');
  $ogmenu_instance_storage->delete($ogmenu_instance_storage->loadMultiple($deleted_ogmenu_ids));

  // Get all the remaining OG Menu instance IDs.
  $ogmenu_ids = $db->query("SELECT entity_id FROM {ogmenu_instance__og_audience} WHERE og_audience_target_id IN(:ids[]) ORDER BY entity_id", [
    ':ids[]' => $ids,
  ])->fetchCol();

  // Get the menu links to be updated.
  $mids = \Drupal::entityQuery('menu_link_content')
    ->condition('menu_name', array_map(function ($i) {
      return "ogmenu-{$i}";
    }, $ogmenu_ids), 'IN')
    ->condition('title', 'Glossary')
    ->execute();
  /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link_content */
  foreach (MenuLinkContentEntity::loadMultiple($mids) as $menu_link_content) {
    $url = $menu_link_content->getUrlObject();
    if ($url->isRouted() && $url->getRouteName() === 'collection.glossary_page') {
      $route_parameters = $url->getRouteParameters();
      if (isset($route_parameters['rdf_entity']) && $route_parameters['rdf_entity'] !== 'http_e_f_fadministracionelectronica_cgob_ces_fctt') {
        $menu_link_content->set('link', [
          'uri' => 'route:collection.glossary_page;rdf_entity=http_e_f_fadministracionelectronica_cgob_ces_fctt',
          'title' => 'Glossary',
        ])->save();
      }
    }
  }
}
