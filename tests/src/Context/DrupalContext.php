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
    $session->visit($this->locatePath('/'));

    // Check if the 'logged-in' class is present on the page.
    $element = $session->getPage();
    return $element->find('css', 'body.user-logged-in');
  }

  /**
   * {@inheritdoc}
   *
   * Identical to the parent method, but allows to use human readable column
   * names, and translates filenames of images in the fixtures folder for the
   * user profile pictures.
   */
  public function createUsers(TableNode $usersTable) {
    foreach ($usersTable->getHash() as $userHash) {
      // Replace the column aliases with the actual field names.
      $userHash = $this->translateUserFieldAliases($userHash);

      // Handle the user profile picture.
      $this->handleFileFields($userHash, 'user', 'user');

      // Split out roles to process after user is created.
      $roles = [];
      if (isset($userHash['roles'])) {
        $roles = explode(',', $userHash['roles']);
        $roles = array_filter(array_map('trim', $roles));
        unset($userHash['roles']);
      }

      $user = (object) $userHash;
      // Set a password.
      if (!isset($user->pass)) {
        $user->pass = $this->getRandom()->name();
      }
      $this->userCreate($user);

      // Assign roles.
      foreach ($roles as $role) {
        $this->getDriver()->userAddRole($user, $role);
      }
    }
  }

}
