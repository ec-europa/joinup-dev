<?php

declare(strict_types = 1);

namespace Drupal\joinup_bundle_class;

use Drupal\file\FileInterface;

/**
 * Reusable methods for entities that have a logo field.
 */
trait LogoTrait {

  /**
   * {@inheritdoc}
   */
  public function getLogoAsRenderArray($display_options = []): array {
    return $this->get($this->getLogoFieldName())->view($display_options);
  }

  /**
   * {@inheritdoc}
   */
  public function getLogoAsFile(): ?FileInterface {
    assert(method_exists($this, 'getMainPropertyValue'), __TRAIT__ . ' depends on JoinupBundleClassFieldAccessTrait. Please include it in your class.');
    $value = $this->getFirstReferencedEntity($this->getLogoFieldName());
    return $value instanceof FileInterface ? $value : NULL;
  }

}
