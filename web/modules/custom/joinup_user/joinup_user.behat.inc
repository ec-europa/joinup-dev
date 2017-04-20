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

}
