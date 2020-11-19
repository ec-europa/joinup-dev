<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;

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
   * @param string|null $bundle
   *   Optional bundle to check. If omitted, the entity can be of any bundle.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The requested entity.
   *
   * @throws \RuntimeException
   *   Thrown when an entity with the given type, label and bundle does not
   *   exist.
   */
  protected static function getEntityByLabel(string $entity_type_id, string $label, ?string $bundle = NULL): EntityInterface {
    $entity_type_manager = \Drupal::entityTypeManager();
    try {
      $storage = $entity_type_manager->getStorage($entity_type_id);
    }
    catch (InvalidPluginDefinitionException $e) {
      throw new \RuntimeException('Storage not found', NULL, $e);
    }
    $entity = $entity_type_manager->getDefinition($entity_type_id);

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
      // Make sure we get a fresh entity from the database, to avoid testing
      // with stale data.
      $storage->resetCache([$result]);
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
      'collection' => 'rdf_entity',
      'content' => 'node',
      'group' => 'rdf_entity',
      'solution' => 'rdf_entity',
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
      throw new \Exception("The menu link with title '{$title}' was not found.");
    }
    // If there are more that one results, we pick up the newest in order to
    // avoid leftovers produced by previous tests.
    krsort($menu_links);
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link */
    $menu_link = reset($menu_links);

    return $menu_link;
  }

  /**
   * Forces a reindex of the entity in search_api.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to reindex.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown when an entity with a non-existing storage is passed.
   */
  protected function forceSearchApiReindex(ContentEntityInterface $entity): void {
    // Invalidate any static cache, so that all computed fields are calculated
    // with updated values (e.g. the "collection" computed field of solutions).
    \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId())->resetCache([$entity->id()]);
    ContentEntity::indexEntity($entity);
  }

}
