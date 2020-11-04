<?php

declare(strict_types = 1);

namespace Drupal\joinup_featured;

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
    return (bool) $this->getMainPropertyValue('field_site_featured');
  }

  /**
   * {@inheritdoc}
   */
  public function feature(): FeaturedContentInterface {
    $this->set('field_site_featured', TRUE);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unfeature(): FeaturedContentInterface {
    $this->set('field_site_featured', FALSE);

    return $this;
  }

}
