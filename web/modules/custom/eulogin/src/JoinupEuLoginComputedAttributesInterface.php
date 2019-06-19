<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin;

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Provides an interface for the joinup_eulogin.computed_attributes service.
 */
interface JoinupEuLoginComputedAttributesInterface {

  /**
   * Returns the replacement value for a given computed attribute.
   *
   * @param string $attribute
   *   The computed attribute name.
   * @param string $original_token
   *   The original token.
   * @param array $cas_attributes
   *   The CAS attributes.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The bubbleable metadata.
   *
   * @return string|null
   *   The value or NULL, if no value can be computed.
   */
  public function getReplacementValue(string $attribute, string $original_token, array $cas_attributes, BubbleableMetadata $bubbleable_metadata): ?string;

}
