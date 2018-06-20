<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;

/**
 * Helper methods to deal with entities.
 */
trait EntityTrait {

  /**
   * Returns the entity with the given type, bundle and label.
   *
   * If multiple entities have the same label then the first one is returned.
   *
   * @param string $entity_type_id
   *   The entity type to check.
   * @param string $label
   *   The label to check.
   * @param string $bundle
   *   Optional bundle to check. If omitted, the entity can be of any bundle.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The requested entity.
   *
   * @throws \RuntimeException
   *   Thrown when an entity with the given type, label and bundle does not
   *   exist.
   */
  protected static function getEntityByLabel(string $entity_type_id, string $label, string $bundle = NULL): EntityInterface {
    $entity_manager = \Drupal::entityTypeManager();
    try {
      $storage = $entity_manager->getStorage($entity_type_id);
    }
    catch (InvalidPluginDefinitionException $e) {
      throw new \RuntimeException('Storage not found', NULL, $e);
    }
    $entity = $entity_manager->getDefinition($entity_type_id);

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

    throw new \RuntimeException("The entity with label '$label' was not found.");
  }

  /**
   * Mapping of human readable entity type names to machine names.
   *
   * @return array
   *   The entity type mapping.
   */
  protected static function entityTypeAliases(): array {
    return [
      'content' => 'node',
    ];
  }

  /**
   * Translates human readable entity types to machine names.
   *
   * @param string $entity_type_label
   *   The human readable entity type. Case insensitive.
   *
   * @return string
   *   The machine name of the entity type.
   */
  protected static function translateEntityTypeAlias(string $entity_type_label): string {
    $entity_type_label = strtolower($entity_type_label);
    $aliases = self::entityTypeAliases();
    if (array_key_exists($entity_type_label, $aliases)) {
      $entity_type_label = $aliases[$entity_type_label];
    }
    return $entity_type_label;
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
  public function getMenuLinkByTitle(string $title): MenuLinkContentInterface {
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
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown when an entity with a non-existing storage is passed.
   */
  protected function forceSearchApiReindex(EntityInterface $entity): void {
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
