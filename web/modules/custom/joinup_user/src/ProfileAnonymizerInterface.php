<?php

declare(strict_types = 1);

namespace Drupal\joinup_user;

use Drupal\user\UserInterface;

/**
 * Provides an interface for the 'joinup_user.profile_anonymizer' service.
 */
interface ProfileAnonymizerInterface {

  /**
   * Anonymizes a user account.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account to be anonymized.
   */
  public function anonymize(UserInterface $account): void;

}
