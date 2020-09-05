<?php

declare(strict_types = 1);

namespace Drupal\joinup;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\Entity\PinnableGroupContentInterface;

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
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupsWherePinned(ContentEntityInterface $entity) {
    return $entity->pinned_in->entity->field_pinned_in->referencedEntities();
  }

}
