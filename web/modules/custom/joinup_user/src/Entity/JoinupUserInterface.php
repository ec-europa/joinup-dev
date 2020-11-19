<?php

declare(strict_types = 1);

namespace Drupal\joinup_user\Entity;

use Drupal\user\UserInterface;

/**
 * Interface for cancelled users.
 */
interface JoinupUserInterface extends UserInterface {

  /**
   * Cancels the user account.
   *
   * @return $this
   *
   * @throws \Exception
   *   If the caller tries to cancel UID1.
   */
  public function cancel(): self;

  /**
   * Indicates whether this user has been cancelled.
   *
   * @return bool
   *   Is the user cancelled?
   */
  public function isCancelled(): bool;

  /**
   * Whether or not the user is a moderator.
   *
   * @return bool
   *   TRUE if the user is a moderator, FALSE otherwise.
   */
  public function isModerator(): bool;

}
