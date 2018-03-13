<?php

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalContext as DrupalExtensionDrupalContext;
use Drupal\joinup\Traits\FileTrait;
use Drupal\joinup\Traits\UserTrait;

/**
 * Provides step definitions for interacting with Drupal.
 */
class DrupalContext extends DrupalExtensionDrupalContext {

  use FileTrait;
  use UserTrait;

  /**
   * {@inheritdoc}
   */
  public function loggedIn() {
    $session = $this->getSession();

    // If the session has not been started yet, or no page has yet been loaded,
    // then this is a brand new test session and the user is not logged in.
    if (!$session->isStarted() || !$page = $session->getPage()) {
      return FALSE;
    }

    // Check if the 'logged-in' class is present on the page.
    return $page->find('css', 'body.user-logged-in');
  }

  /**
   * {@inheritdoc}
   *
   * Similar to the parent method, but allows to use human readable column
   * names, and translates filenames of images in the fixtures folder for the
   * user profile pictures.
   */
  public function createUsers(TableNode $usersTable) {
    foreach ($usersTable->getHash() as $userHash) {
      $this->createUser($userHash);
    }
  }

}
