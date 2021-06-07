<?php

declare(strict_types = 1);

namespace Drupal\topic\Entity;

use Drupal\Core\Entity\EntityInterface;

/**
 * Reusable methods for entities that reference topics.
 */
trait TopicReferencingEntityTrait {

  /**
   * {@inheritdoc}
   */
  public function getTopics(): array {
    assert(method_exists($this, 'getReferencedEntities'), __TRAIT__ . ' depends on JoinupBundleClassFieldAccessTrait. Please include it in your class.');
    return array_filter($this->getReferencedEntities('field_topic'), function (EntityInterface $entity): bool {
      return $entity instanceof TopicInterface;
    });
  }

}
