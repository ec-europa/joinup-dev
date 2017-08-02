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

  /**
   * Searches the delete confirmation link from an email and navigates to it.
   *
   * @param string $user
   *   The user name.
   *
   * @throws \Exception
   *   Thrown when a user is not found.
   *
   * @When I click the delete confirmation link for (the user ):user from the last email
   */
  public function clickDeleteConfirmationLink($user) {
    $user = $this->getUserByName($user);
    if (empty($user)) {
      throw new \Exception("User {$user->getAccountName()} was not found.");
    }

    $user_email = $user->getEmail();
    \Drupal::state()->resetCache();
    $mails = \Drupal::state()->get('system.test_mail_collector');
    if (empty($mails)) {
      throw new Exception('No mail was sent.');
    }

    $link = NULL;
    $matches = [];
    foreach ($mails as $mail) {
      if ($mail['to'] !== $user_email) {
        continue;
      }

      $pattern = '#https?://.*?/user/' . $user->id() . '/cancel/confirm/\d+/.*?[\r\n\s]#';
      $body = (string) $mail['body'];
      preg_match_all($pattern, $body, $matches);
      $matches = array_filter($matches);
      if (empty($matches[0])) {
        continue;
      }
      break;
    }

    if (empty($matches)) {
      throw new Exception('Deletion confirmation url was not found');
    }
    $url = $matches[0][0];
    $this->visitPath($url);
  }

}
