<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * EU Login Behat sub-context.
 */
class JoinupEuLoginContext extends RawDrupalContext {

  /**
   * Disables the limited access functionality during tests run.
   *
   * A user whose account in not yet linked with an EU Login account has limited
   * access to website features. They can login only by using the one-time-login
   * mechanism, but one-time-login is meant only to allow password recovery. On
   * the other hand, in testing, we use the one-time-login mechanism to perform
   * the authentication, instead of following the CAS login process.
   *
   * @BeforeSuite
   */
  public static function disableLimitedAccess(): void {
    \Drupal::state()->set('joinup_eulogin.disable_limited_access', TRUE);
  }

  /**
   * Restores the limited access functionality after tests run.
   *
   * @AfterSuite
   *
   * @see self::disableLimitedAccess()
   */
  public static function restoreLimitedAccess(): void {
    \Drupal::state()->delete('joinup_eulogin.disable_limited_access');
  }

}
