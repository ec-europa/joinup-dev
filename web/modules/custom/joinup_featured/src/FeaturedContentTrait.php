<?php

declare(strict_types = 1);

namespace Drupal\joinup_featured;

use Drupal\meta_entity\Entity\MetaEntityInterface;

/**
 * Reusable methods for entities that can be featured site wide.
 *
 * @todo Once we are on PHP 7.3 the JoinupBundleClassMetaEntityTrait and
 *   JoinupBundleClassFieldAccessTrait should be included here.
 */
trait FeaturedContentTrait {

  /**
   * {@inheritdoc}
   */
  public function isFeatured(): bool {
    $meta_entity = $this->getMetaEntity('featured');
    if (!$meta_entity instanceof MetaEntityInterface) {
      return FALSE;
    }

    /** @var \Drupal\Core\Field\FieldItemListInterface $item_list */
    $item_list = $meta_entity->get('field_featured');

    if ($item_list->isEmpty()) {
      return FALSE;
    }

    return (bool) $item_list->value;
  }

  /**
   * {@inheritdoc}
   */
  public function feature(): FeaturedContentInterface {
    // Only update the featured status in the database if needed.
    if (!$this->isFeatured()) {
      $meta_entity = $this->getMetaEntity('featured');
      if (empty($meta_entity)) {
        $meta_entity = $this->createMetaEntity('featured');
      }
      $meta_entity->set('field_featured', TRUE)->save();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unfeature(): FeaturedContentInterface {
    $meta_entity = $this->getMetaEntity('featured');

    if (!empty($meta_entity)) {
      $meta_entity->delete();
    }

    return $this;
  }

}
