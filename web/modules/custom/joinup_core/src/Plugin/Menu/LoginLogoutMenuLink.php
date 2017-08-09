<?php

namespace Drupal\joinup_core\Plugin\Menu;

use Drupal\user\Plugin\Menu\LoginLogoutMenuLink as CoreLoginLogoutMenuLink;

/**
 * A menu link that shows "Sign in" or "Sign out" as appropriate.
 */
class LoginLogoutMenuLink extends CoreLoginLogoutMenuLink {

  /**
   * {@inheritdoc}
   *
   * Override the default behaviour to change the default 'Log' term.
   */
  public function getTitle() {
    if ($this->currentUser->isAuthenticated()) {
      return $this->t('Sign out');
    }
    else {
      return $this->t('Sign in');
    }
  }

}
