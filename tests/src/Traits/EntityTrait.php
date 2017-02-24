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

}
