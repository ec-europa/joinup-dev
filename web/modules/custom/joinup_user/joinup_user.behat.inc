<?php

/**
 * @file
 * Contains \JoinupUserSubContext.
 */

declare(strict_types = 1);

use Behat\Gherkin\Node\TableNode;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\DrupalExtension\Hook\Scope\BeforeUserCreateScope;
use Drupal\joinup\Traits\MailCollectorTrait;
use Drupal\joinup\Traits\UserTrait;
use Drupal\joinup\Traits\UtilityTrait;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for testing users.
 */
class JoinupUserSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use MailCollectorTrait;
  use StringTranslationTrait;
  use UserTrait;
  use UtilityTrait;

  /**
   * Navigates to the canonical page display of a collection.
   *
   * @param string $user
   *   The user name.
   *
   * @When I go to the (public )profile of :user
   * @When I visit the (public )profile of :user
   */
  public function visitUserPublicProfile(string $user): void {
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
  public function visitOneTimeLogIn(string $user): void {
    $user = $this->getUserByName($user);
    if (empty($user)) {
      throw new \Exception("User {$user->getAccountName()} was not found.");
    }

    $this->visitPath(user_pass_reset_url($user) . '/login');
  }

  /**
   * Clicks an HTML link in the last email matching its title.
   *
   * @param string $title
   *   The link title.
   * @param string $user
   *   The user that the email was sent to.
   *
   * @throws \Exception
   *   Thrown when the user or the link was not found.
   *
   * @Given I click the :title link from the email sent to :user
   */
  public function clickNamedLinkInEmail(string $title, string $user): void {
    $mails = $this->getUserMails($this->getUserByName($user));
    $matches = [];
    foreach ($mails as $mail) {
      $pattern = '#href="([^"]*?)"[^>]*?>' . $title . '#';
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
      $message = "No link with named {$title} was found in any of the emails sent to {$user}";
      throw new Exception($message);
    }
    $url = reset($matches[1]);
    $this->visitPath($url);
  }

  /**
   * Checks that a given image is present in the page.
   *
   * This step is copied because the original requires that the link ends in the
   * extension while modern techniques add extra query parameters in the URI.
   *
   * @param string $filename
   *   The filename of the image to check.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   Thrown when the image is not present in the page.
   *
   * @Then I (should )see the avatar :filename
   */
  public function assertImagePresent(string $filename): void {
    // Drupal appends an underscore and a number to the filename when duplicate
    // files are uploaded, for example when a test is run more than once.
    // We split up the filename and extension and match for both.
    $parts = pathinfo($filename);
    $extension = $parts['extension'];
    $filename = $parts['filename'];
    $this->assertSession()->elementExists('css', "[src*='$filename.$extension']");
  }

  /**
   * Checks that the user with the given name has the given profile field data.
   *
   * Table format:
   * | Username   | Mr Bond                    |
   * | Password   | Bond007                    |
   * | E-mail     | james.bond@mi5.org         |
   * | Multivalue | Values,separated,by,commas |
   *
   * @param \Behat\Gherkin\Node\TableNode $profile_data_table
   *   A table containing the user profile field data to check.
   * @param string $username
   *   The name of the user to check.
   *
   * @throws \Exception
   *   Thrown when a field in the table does not exist.
   *
   * @Then the user :username should have the following data in their user profile:
   */
  public function assertUserFields(TableNode $profile_data_table, string $username): void {
    $user = $this->getUserByName($username);
    $profile_data = $this->translateUserFieldAliases($profile_data_table->getRowsHash());

    foreach ($profile_data as $field => $values) {
      $expected_values = explode(',', $values);
      $actual_values = $user->get($field)->getValue();
      Assert::assertSameSize($expected_values, $actual_values);
      foreach ($expected_values as $key => $expected_value) {
        $actual_value = $user->get($field)->get($key)->value;
        Assert::assertSame($expected_value, $actual_value);
      }
    }
  }

  /**
   * Replaces human readable values with their real counterparts for users.
   *
   * @param \Drupal\DrupalExtension\Hook\Scope\BeforeUserCreateScope $scope
   *   An object containing the entity properties and fields that are to be used
   *   for creating the user as properties on the object.
   *
   * @throws \Exception
   *   Thrown when some of the values are not one of the expected ones.
   *
   * @BeforeUserCreate
   */
  public static function massageFieldsBeforeUserCreate(BeforeUserCreateScope $scope): void {
    $user = $scope->getEntity();

    self::convertObjectPropertyValues($user, 'status', [
      'blocked' => 0,
      'active' => 1,
    ]);
  }

}
