<?php

declare(strict_types = 1);

namespace Drupal\joinup_user;

use Drupal\joinup_user\Entity\JoinupUserInterface;

/**
 * Interface for services that assist with sending notifications.
 */
interface JoinupUserNotificationHelperInterface {

  /**
   * Sends a notification to the user that a moderator changed their account.
   *
   * @param \Drupal\joinup_user\Entity\JoinupUserInterface $user
   *   The affected user.
   */
  public function notifyOnAccountChange(JoinupUserInterface $user): void;

}
