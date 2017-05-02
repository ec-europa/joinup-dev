<?php

namespace Drupal\joinup\Traits;

/**
 * Helper methods when dealing with Nodes.
 */
trait NodeTrait {

  /**
   * Returns the node with the given title and bundle.
   *
   * If multiple nodes have the same title the first one will be returned.
   *
   * @param string $title
   *   The node's title.
   * @param string $bundle
   *   Optional content entity bundle.
   *
   * @return \Drupal\node\Entity\Node
   *   The node.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a node with the given name does not exist.
   */
  public static function getNodeByTitle($title, $bundle = NULL) {
    $query = \Drupal::entityQuery('node')
      ->condition('title', $title)
      ->range(0, 1);
    if (!empty($bundle)) {
      $query->condition('type', $bundle);
    }
    $result = $query->execute();

    if (empty($result)) {
      if ($bundle) {
        $message = "The '$bundle' with the name '$title' does not exist.";
      }
      else {
        $message = "The node with the name '$title' does not exist.";
      }
      throw new \InvalidArgumentException($message);
    }

    // Reload from database to avoid caching issues and get latest version.
    $id = reset($result);
    return \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged($id);
  }

}
