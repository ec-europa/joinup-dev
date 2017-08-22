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
   * Navigates to the one time sign in page of the user.
   *
   * @param string $user
   *   The user name.
   *
   * @throws \Exception
   *   Thrown when a user is not found.
   *
   * @When I go to the one time sign in page of (the user ):user
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

      // @todo: Set a more appropriate regex when the domain name of CPHP allows
      // us to convert the urls automatically.
      // @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3790
      $pattern = '#(https?://.*?/user/' . $user->id() . '/cancel/confirm/\d+/.*?)[\r\n\s\"<>]#';
      $body = (string) $mail['body'];
      preg_match_all($pattern, $body, $matches);
      $matches = array_filter($matches);
      // $matches[0] are urls with the characters outside the matching
      // parenthesis. $matches[1] are the matches restricted by the parenthesis
      // tags in the regex. $matches[1] contain the clean urls.
      if (empty($matches[1])) {
        continue;
      }
      break;
    }

    if (empty($matches)) {
      $message = t("Deletion confirmation url was not found in the mail body: \n@body", [
        '@body' => $body,
      ]);
      throw new Exception($message);
    }
    $url = reset($matches[1]);
    $this->visitPath($url);
  }

  /**
   * Checks that a given image is present in the page.
   *
   * This step is copied because the original requires that the link ends in the
   * extension while modern techniques add extra query parameters in the uri.
   *
   * @Then I (should )see the avatar :filename
   */
  public function assertImagePresent($filename) {
    // Drupal appends an underscore and a number to the filename when duplicate
    // files are uploaded, for example when a test is run more than once.
    // We split up the filename and extension and match for both.
    $parts = pathinfo($filename);
    $extension = $parts['extension'];
    $filename = $parts['filename'];
    $this->assertSession()->elementExists('css', "[src*='$filename.$extension']");
  }

}
