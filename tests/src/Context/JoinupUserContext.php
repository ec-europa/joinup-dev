<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\DrupalExtension\Hook\Scope\BeforeUserCreateScope;
use Drupal\joinup\Traits\MailCollectorTrait;
use Drupal\joinup\Traits\UserTrait;
use Drupal\joinup\Traits\UtilityTrait;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Behat step definitions for testing users.
 */
class JoinupUserContext extends RawDrupalContext {

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
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function visitUserPublicProfile(string $user): void {
    $this->visitPath($this->getUserByName($user)->toUrl()->toString());
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

    $this->visitPath($this->getOneTimeLoginUrl($user) . '/login');
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
      throw new \Exception($message);
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
    // We only check up to the filename and not the extension as the xpath
    // itself is long enough to ensure the extension part.
    $parts = pathinfo($filename);
    $host = \Drupal::request()->getSchemeAndHttpHost();
    $filename = $parts['filename'];
    $xpath = "//div[contains(concat(' ', normalize-space(@class), ' '), ' featured__logo ') and contains(@style, 'background-image: url({$host}/sites/default/files/{$filename}')]";
    $this->assertSession()->elementExists('xpath', $xpath);
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

  /**
   * Asserts that a user account is cancelled.
   *
   * @param string $name
   *   The user name.
   * @param string $status
   *   The user status: 'active', 'blocked' or 'cancelled'.
   *
   * @throws \Exception
   *   If an invalid status has been passed.
   *
   * @Then the :name (user )account is :status
   */
  public function assertUserStatus(string $name, string $status): void {
    $allowed_statuses = [
      'active' => 1,
      'blocked' => 0,
      'cancelled' => -1,
    ];

    if (!isset($allowed_statuses[$status])) {
      throw new \Exception("Invalid '$status' status. Allowed values are 'active', 'blocked' or 'cancelled'.");
    }
    $uids = \Drupal::entityQuery('user')
      ->condition('name', $name)
      ->condition('status', $allowed_statuses[$status])
      ->execute();

    if (!$uids) {
      throw new ExpectationFailedException("The user '$name' doesn't exist or is not $status.");
    }
  }

  /**
   * Asserts that the user with the given name doesn't exist.
   *
   * @param string $name
   *   The user name.
   *
   * @Then the :name user doesn't exist
   */
  public function userNotExists(string $name): void {
    if (\Drupal::entityQuery('user')->condition('name', $name)->execute()) {
      throw new ExpectationFailedException("The user $name exists but it should not.");
    }
  }

  /**
   * Generates a unique URL for a user to log in and reset their password.
   *
   * @param \Drupal\user\UserInterface $account
   *   An object containing the user account.
   *
   * @return string
   *   A unique URL that provides a one-time log in for the user.
   */
  protected function getOneTimeLoginUrl(UserInterface $account): string {
    $timestamp = time();
    return Url::fromRoute('user.reset', [
      'uid' => $account->id(),
      'timestamp' => $timestamp,
      'hash' => user_pass_rehash($account, $timestamp),
    ],
    [
      'absolute' => true,
      'language' => \Drupal::languageManager()->getLanguage($account->getPreferredLangcode()),
      // The base URL is derived by the Symfony request handler from
      // the global variables set by the web server, i.e. REQUEST_URI
      // or similar. Since Behat tests are run from the command line
      // this request context is not available and we need to set the
      // base URL manually.
      // @todo Remove this workaround once this is fixed in core.
      // @see https://www.drupal.org/project/drupal/issues/2548095
      'base_url' => $GLOBALS['base_url'],
    ])->toString();
  }

}
