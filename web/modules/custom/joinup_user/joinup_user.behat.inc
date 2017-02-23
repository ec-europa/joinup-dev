<?php

/**
 * @file
 * Contains \JoinupUserSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;

/**
 * Behat step definitions for testing users.
 */
class JoinupUserSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

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
    $this->visitPath($this->loadUserByName($user)->url());
  }

  /**
   * Loads a user by its name.
   *
   * @param string $name
   *   The name of the user.
   *
   * @return \Drupal\user\Entity\User
   *   The loaded user entity.
   *
   * @throws \Exception
   *   Thrown when a user with the provided name cannot be found.
   */
  protected function loadUserByName($name) {
    $user = user_load_by_name($name);

    if (!$user) {
      throw new \Exception("The user with name '$name' was not found.");
    }

    /** @var \Drupal\user\Entity\User $user */
    return $user;
  }

}
