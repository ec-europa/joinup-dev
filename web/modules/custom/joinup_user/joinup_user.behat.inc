<?php

/**
 * @file
 * Contains \JoinupUserSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\UserTrait;

/**
 * Behat step definitions for testing users.
 */
class JoinupUserSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use UserTrait;

  /**
   * Navigates to the canonical page display of a collection.
   *
   * @param string $user
   *   The user name.
   *
   * @When (I )go to the (public )profile of :user
   * @When (I )visit the (public )profile of :user
   */
  public function visitUserPublicProfile($user) {
    $this->visitPath($this->getUserByName($user)->url());
  }

  /**
   * Navigates to the one time log in page of the user.
   *
   * @param string $user
   *   The user name.
   *
   * @throws \Exception
   *   Thrown when a user is not found.
   *
   * @When I go to the one time log in page of (the user ):user
   */
  public function visitOneTimeLogIn($user) {
    $user = $this->getUserByName($user);
    if (empty($user)) {
      throw new \Exception("User {$user->getAccountName()} was not found.");
    }

    $this->visitPath(user_pass_reset_url($user) . '/login');
  }

}
