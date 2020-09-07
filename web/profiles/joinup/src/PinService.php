<?php

declare(strict_types = 1);

namespace Drupal\joinup;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\Entity\PinnableGroupContentInterface;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;

/**
 * A service to handle pinned entities.
 */
class PinService implements PinServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function isEntityPinned(PinnableGroupContentInterface $entity, ?GroupInterface $group = NULL) {
    if (empty($group)) {
      return !$entity->pinned_in->entity->field_pinned_in->isEmpty();
    }
    // @todo We can skip the full loading of the referenced entities.
    foreach ($entity->pinned_in->entity->field_pinned_in->referencedEntities() as $rdf) {
      if ($rdf->id() === $group->id()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityPinned(PinnableGroupContentInterface $entity, GroupInterface $group, bool $pinned) {
    if ($pinned) {
      $entity->pinned_in->entity->field_pinned_in->appendItem($group->id());
      // $entity->pin($group);
    }
    else {
      $entity->pinned_in->entity->field_pinned_in->filter(function ($item) use ($group) {
        /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
        return $item->target_id !== $group->id();
      });
      // $entity->unpin($group);
    }
    $entity->pinned_in->entity->save();

    // @todo Move the next two to hook_meta_entity_save().
    // Reindex the parent entity since the pinned status affects the ordering of
    // search results: pinned entities are shown at the top.
    ContentEntity::indexEntity($entity);

    // Invalidate caches of the parent entity so that the pin icon will be shown
    // or hidden according to the new pinned status.
    // @see https://www.drupal.org/project/meta_entity/issues/3169560
    Cache::invalidateTags($entity->getCacheTagsToInvalidate());
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupsWherePinned(ContentEntityInterface $entity) {
    return $entity->pinned_in->entity->field_pinned_in->referencedEntities();
  }

}
