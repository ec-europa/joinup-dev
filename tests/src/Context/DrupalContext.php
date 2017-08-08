<?php

namespace Drupal\joinup\Context;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalContext as DrupalExtensionDrupalContext;
use Drupal\facets\Exception\Exception;
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
   * Similar to the parent method, but allows to use human readable column
   * names, and translates filenames of images in the fixtures folder for the
   * user profile pictures.
   */
  public function createUsers(TableNode $usersTable) {
    foreach ($usersTable->getHash() as $userHash) {
      $this->createUser($userHash);
    }
  }

  /**
   * Test.
   *
   * @AfterScenario
   */
  public function myCleanUsers(AfterScenarioScope $scope) {
    try {
      $this->cleanUsers();
    }
    catch (Exception $e) {
      var_dump($scope->getFeature());
    }
  }

}
