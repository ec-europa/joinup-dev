<?php

namespace Drupal\joinup\Traits;

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
   * Retrieves a user by name.
   *
   * @param string $name
   *   The name of the user.
   *
   * @return \Drupal\user\Entity\User
   *   The loaded user entity.
   *
   * @throws \Exception
   *   Thrown when a user with the provided name is not found.
   */
  protected function getUserByName($name) {
    $user = user_load_by_name($name);

    if (!$user) {
      throw new \Exception("The user with name '$name' was not found.");
    }

    /** @var \Drupal\user\Entity\User $user */
    return $user;
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

}
