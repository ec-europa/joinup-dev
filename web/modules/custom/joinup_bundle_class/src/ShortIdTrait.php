<?php

declare(strict_types = 1);

namespace Drupal\joinup_bundle_class;

/**
 * Reusable methods for entities that have a short ID field.
 */
trait ShortIdTrait {

  /**
   * {@inheritdoc}
   */
  public function getShortId(): ?string {
    assert(method_exists($this, 'getMainPropertyValue'), __TRAIT__ . ' depends on JoinupBundleClassFieldAccessTrait. Please include it in your class.');
    $value = $this->getMainPropertyValue('field_short_id');
    return $value ? (string) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasShortId(): bool {
    return (bool) $this->getShortId();
  }

}
