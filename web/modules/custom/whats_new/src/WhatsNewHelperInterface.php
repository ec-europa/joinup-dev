<?php

declare(strict_types = 1);

namespace Drupal\whats_new;

use Drupal\Core\Entity\EntityInterface;
use Drupal\flag\FlaggingInterface;
use Drupal\node\NodeInterface;

/**
 * Interface for the WhatsNewHelper service.
 */
interface WhatsNewHelperInterface {

  /**
   * Checks whether a menu item with enabled flagging exists for this entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to search links for.
   *
   * @return bool
   *   True if there is at least one link in the support menu that is enabled
   *   and has the flagging flag set to 1.
   */
  public function hasFlagEnabledMenuLinksForEntity(EntityInterface $entity): bool;

  /**
   * Returns a flagging entity related to the node and the current user.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return \Drupal\flag\FlaggingInterface|null
   *   The flagging entity or null if none returned.
   */
  public function getFlaggingForNode(NodeInterface $node): ?FlaggingInterface;

  /**
   * Adds a flag to the given entity for the current user.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node related to the flag.
   */
  public function setFlaggingForNode(NodeInterface $node): void;

}
