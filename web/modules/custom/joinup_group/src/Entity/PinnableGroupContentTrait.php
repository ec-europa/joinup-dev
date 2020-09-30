<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Entity;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\Exception\MissingDataException;

/**
 * Reusable methods for pinnable group content entities.
 *
 * @todo Once we are on PHP 7.3 the JoinupBundleClassMetaEntityTrait
 *   should be included here.
 */
trait PinnableGroupContentTrait {

  /**
   * {@inheritdoc}
   */
  public function isPinned(?GroupInterface $group = NULL): bool {
    if (!$meta_entity = $this->getMetaEntity('pinned_in')) {
      return FALSE;
    }

    /** @var \Drupal\joinup_federation\RdfEntityReferenceFieldItemList $item_list */
    $item_list = $meta_entity->get('field_pinned_in');

    if (empty($group)) {
      return !$item_list->isEmpty();
    }

    return array_reduce($item_list->getValue(), function (bool $carry, array $properties) use ($group) {
      return $carry || ($properties['target_id'] ?? '') === $group->id();
    }, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function pin(?GroupInterface $group = NULL): PinnableGroupContentInterface {
    // Default to the parent group.
    $group = $group ?: $this->getGroup();

    // Only update the pinned status in the database if needed.
    if (!$this->isPinned($group)) {
      $meta_entity = $this->getMetaEntity('pinned_in');
      if (empty($meta_entity)) {
        $meta_entity = $this->createMetaEntity('pinned_in');
      }
      $meta_entity->get('field_pinned_in')->appendItem($group->id());
      $meta_entity->save();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unpin(?GroupInterface $group = NULL): PinnableGroupContentInterface {
    // Default to the parent group.
    $group = $group ?: $this->getGroup();

    // Only update the pinned status in the database if needed.
    if ($this->isPinned($group)) {
      $meta_entity = $this->getMetaEntity('pinned_in');

      /** @var \Drupal\joinup_federation\RdfEntityReferenceFieldItemList $item_list */
      $item_list = $meta_entity->get('field_pinned_in');

      $item_list->filter(function (EntityReferenceItem $item) use ($group): bool {
        return $item->target_id !== $group->id();
      });

      // If the entity is no longer pinned in any groups, delete the meta entity
      // rather than updating it. This ensures that the database will not retain
      // a large number of empty meta entities for old content that was pinned
      // at some point in the past but will probably never be pinned again.
      if ($item_list->isEmpty()) {
        $meta_entity->delete();
      }
      else {
        $meta_entity->save();
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPinnedGroupIds(): array {
    $ids = [];

    $meta_entity = $this->getMetaEntity('pinned_in');
    foreach ($meta_entity->get('field_pinned_in') as $item) {
      if ($item instanceof EntityReferenceItem) {
        try {
          if ($target_id = $item->get('target_id')->getValue()) {
            $ids[] = $target_id;
          }
        }
        catch (MissingDataException|\InvalidArgumentException $e) {
        }
      }
    }

    return $ids;
  }

}
