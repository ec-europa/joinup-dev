<?php

declare(strict_types = 1);

namespace Drupal\joinup_event\Entity;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Interface for event entities in Joinup.
 */
interface EventInterface extends NodeInterface {

  /**
   * Returns the event location.
   *
   * @return string|null
   *   The event location, or NULL if the event doesn't have a location.
   */
  public function getLocation(): ?string;

  /**
   * Returns the online location for the event.
   *
   * @return string|null
   *   The online location, or NULL if the event doesn't have one.
   */
  public function getOnlineLocation(): ?string;

  /**
   * Returns the web URL.
   *
   * @return \Drupal\Core\Url|null
   *   The web URL, or NULL if the event doesn't have one.
   */
  public function getWebUrl(): ?Url;

}
