<?php

namespace Drupal\joinup\Traits;

use Drupal\Core\Entity\EntityInterface;

/**
 * Helper methods to deal with entities.
 */
trait EntityTrait {

  /**
   * Returns the entity with the given type, bundle and label.
   *
   * If multiple entities have the same label then the first one is returned.
   *
   * @param string $entity_type
   *   The entity type to check.
   * @param string $label
   *   The label to check.
   * @param string $bundle
   *   Optional bundle to check. If omitted, the entity can be of any bundle.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The requested entity.
   *
   * @throws \Exception
   *   Thrown when an entity with the given type, label and bundle does not
   *   exist.
   */
  protected function getEntityByLabel($entity_type, $label, $bundle = NULL) {
    $entity_manager = \Drupal::entityTypeManager();
    $storage = $entity_manager->getStorage($entity_type);
    $entity = $entity_manager->getDefinition($entity_type);

    $query = $storage->getQuery()
      ->condition($entity->getKey('label'), $label)
      ->range(0, 1);

    // Optionally filter by bundle.
    if ($bundle) {
      $query->condition($entity->getKey('bundle'), $bundle);
    }

    $result = $query->execute();

    if ($result) {
      $result = reset($result);
      return $storage->load($result);
    }

    throw new \Exception("The entity with label '$label' was not found.");
  }

  /**
   * Returns a MenuLinkContent entity from the database.
   *
   * The function assumes that there are not duplicate entries with the same
   * title so it returns the first of the results.
   *
   * @param string $title
   *   The label of the menu item.
   *
   * @return \Drupal\menu_link_content\MenuLinkContentInterface
   *   The MenuLinkContent entity.
   *
   * @throws \Exception
   *    Thrown when the menu item is not found.
   */
  public function getMenuLinkByTitle($title) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $menu_links = $entity_type_manager->getStorage('menu_link_content')->loadByProperties(
      ['title' => $title]
    );
    if (empty($menu_links)) {
      throw new \Exception("The menu parent with title '{$title}' was not found.");
    }

    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $parent_link */
    return reset($menu_links);
  }

  /**
   * Forces a reindex of the entity in search_api.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to reindex.
   */
  protected function forceSearchApiReindex(EntityInterface $entity) {
    // Invalidate any static cache, so that all computed fields are calculated
    // with updated values.
    // For example, the "collection" computed field of solutions.
    \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId())->resetCache([$entity->id()]);
    // In order to avoid copying code from search_api_entity_update(), we
    // need to fake an update event. Said function requires the "original"
    // property to be populated, so just fill it with the entity itself.
    $entity->original = $entity;
    search_api_entity_update($entity);
  }

}
