<?php

namespace Drupal\joinup\Traits;
use Drupal\node\Entity\Node;

/**
 * Helper methods when dealing with Nodes.
 */
trait NodeTrait {

  /**
   * Returns the node with the given title and bundle.
   *
   * If multiple nodes have the same title,
   * the first one will be returned.
   *
   * @param string $title
   *   The node's title.
   * @param string $bundle
   *   The content entity bundle.
   *
   * @return \Drupal\node\Entity\Node
   *   The custom page node.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a custom page with the given name does not exist.
   */
  public static function getNodeByTitle($title, $bundle) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', $bundle)
      ->condition('title', $title)
      ->range(0, 1);
    $result = $query->execute();

    if (empty($result)) {
      throw new \InvalidArgumentException("The '$bundle' with the name '$title' does not exist.");
    }

    return Node::load(reset($result));
  }

}
