<?php

declare(strict_types = 1);

namespace Drupal\oe_newsroom_newsletter\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Interface for Newsletter field type items.
 */
interface NewsletterItemInterface extends FieldItemInterface {

  /**
   * Returns the Newsroom universe acronym for the newsletter.
   *
   * @return string|null
   *   The Newsroom universe acronym, or NULL if the acronym has not been
   *   configured yet in the field settings.
   */
  public function getUniverse(): ?string;

  /**
   * Returns the Newsroom service ID for the newsletter.
   *
   * @return int|null
   *   The Newsroom service ID, or NULL if the service ID has not been
   *   configured yet in the field settings.
   */
  public function getServiceId(): ?int;

  /**
   * Returns whether or not the newsletter subscriptions are enabled.
   *
   * This will also return FALSE if one or both of the required Newsroom
   * parameters are not set (universe acronym and service ID).
   *
   * @return bool
   *   TRUE if the newsletter subscriptions are enabled, FALSE otherwise.
   */
  public function isEnabled(): bool;

}
