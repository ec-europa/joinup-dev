<?php

declare(strict_types = 1);

namespace Drupal\joinup_menu;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Helper methods for dealing with menus in Joinup.
 */
class JoinupMenuHelper implements JoinupMenuHelperInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Builds a new custom page OG menu links updater service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function loadEntitiesFromMenuItems(array $menu_items): array {
    $items = [];
    $storage_handlers = [];

    // Check if storage handlers are registered, so we do not need to introduce
    // hard dependencies on modules providing entity types.
    foreach (['node', 'rdf_entity'] as $entity_type) {
      try {
        $storage_handlers[$entity_type] = $this->entityTypeManager->getStorage($entity_type);
      }
      catch (PluginNotFoundException | InvalidPluginDefinitionException $e) {
      }
    }

    foreach ($menu_items as $menu_item) {
      $url_parameters = $menu_item->getUrlObject()->getRouteParameters();
      foreach (array_keys($storage_handlers) as $entity_type) {
        if (isset($url_parameters[$entity_type])) {
          $items[] = $storage_handlers[$entity_type]->load($url_parameters[$entity_type]);
          break;
        }
      }
    }

    return $items;
  }

}
