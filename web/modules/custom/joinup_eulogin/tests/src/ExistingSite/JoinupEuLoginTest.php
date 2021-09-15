<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_eulogin\ExistingSite;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\cas\Traits\CasTestTrait;
use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\user\Entity\User;
use weitzman\LoginTrait\LoginTrait;

/**
 * Tests the EU Login.
 *
 * @group joinup_eulogin
 */
class JoinupEuLoginTest extends JoinupExistingSiteTestBase {

  use AssertMailTrait;
  use CasTestTrait;
  use LoginTrait;

  /**
   * Testing account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * Tests the replacement of password on a successful account linking.
   */
  public function testPasswordImpersonation(): void {
    // Create an EU Login user.
    $authname = $this->randomMachineName();
    $email = "{$authname}@example.com";
    $eulogin_pass = user_password();
    $this->createCasUser($authname, $email, $eulogin_pass);

    // Create a local user.
    $local_account = $this->createUser([], NULL, FALSE, [
      'pass' => $this->generateRandomPassword(),
    ]);
    // Store the hashed password in a variable for later comparison.
    $original_hashed_pass = $local_account->getPassword();

    // Log in with EU Login.
    $this->casLogin($email, $eulogin_pass);

    $page = $this->getSession()->getPage();

    // Select the option that allows pairing with the local account.
    $page->selectFieldOption('account_exist', 'yes');

    // Use the local credentials to pair the account.
    $page->fillField('Email or username', $local_account->getAccountName());
    $page->fillField('Password', $local_account->pass_raw);
    $page->pressButton('Sign in');
    $this->assertSession()->pageTextContains("Your EU Login account {$authname} has been successfully linked to your local account {$local_account->getAccountName()}.");

    $final_hashed_pass = User::load($local_account->id())->getPassword();

    // Check that a random password has been set. As we cannot intercept the
    // generated random password, and neither can we reverse engineer the hash,
    // we only compare the original and the final local account password hashes.
    $this->assertNotSame($final_hashed_pass, $original_hashed_pass);
  }

  /**
   * Test limited access for one-time-login sessions.
   */
  public function testLimitedAccess(): void {
    // This test needs the limited access functionality enabled.
    $this->state->delete('joinup_eulogin.disable_limited_access');

    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Anonymous cannot see '/user/limited-access' as page.
    $this->drupalGet('/user/limited-access');
    $assert->statusCodeEquals(404);

    $this->account = $this->createUser([], NULL, FALSE, [
      'field_user_first_name' => $this->randomString(),
      'field_user_family_name' => $this->randomString(),
    ]);
    // Store the hashed password in a variables.
    $original_hashed_pass = $this->account->getPassword();

    $this->drupalGet('/user/password');

    $this->drupalPostForm(NULL, ['name' => $this->account->getEmail()], 'Submit');
    $assert->pageTextContains('Further instructions have been sent to your email address.');

    $this->assertMailString('id', 'user_password_reset', 1);
    $this->assertMailString('subject', 'Please confirm the request of a new password.', 1);
    $this->drupalGet($this->extractPasswordResetUrlFromMail());
    $page->pressButton('Log in');
    $assert->pageTextContains('You have just used your one-time login link. It is no longer necessary to use this link to log in. Please change your password.');

    // Check that the user is still able to contact the support.
    $this->assertAccess('/contact', 'Contact');
    // Check that the user is still able to access its profile page.
    $this->assertAccess('/user', $this->account->getDisplayName());
    // Check that the user is still able to access its account edit page.
    $this->assertAccess($this->account->toUrl('edit-form'), $this->account->getDisplayName());

    // Set a new password.
    $page->fillField('Current password', $this->account->passRaw);
    $new_pass = $this->generateRandomPassword();
    $page->fillField('Password', $new_pass);
    $page->fillField('Confirm password', $new_pass);
    $page->pressButton('Save');

    // Check that the password has been changed.
    $assert->addressEquals("/user/{$this->account->id()}");
    $assert->pageTextContains('The changes have been saved.');
    $this->assertNotSame($original_hashed_pass, User::load($this->account->id())->getPassword());

    // Try to navigate to pages that are not accessible.
    $this->assertLimitedAccess('<front>');
    $this->assertLimitedAccess('/collections');
    $this->assertLimitedAccess('/solutions');
    $this->assertLimitedAccess('/keep-up-to-date');
    $this->assertLimitedAccess('/search');

    // Check that the link to EU Login works. By clicking this link the user
    // will be logged out of their Drupal account and will be ready to log in
    // using EU Login.
    $this->clickLink('EU Login');
    $assert->pageTextContains('Sign in to continue');

    // Check that the redirect to limited access page has not been cached. The
    // user is now anonymous, so should be again able to access all pages.
    $this->assertAccess('<front>');
    $this->assertAccess('/collections');
    $this->assertAccess('/solutions');
    $this->assertAccess('/keep-up-to-date');
    $this->assertAccess('/search');

    // Log in as an special user with 'bypass limited access' permission. These
    // users are intended for maintenance or demonstration purposes.
    $this->bypassReadOnlyConfig();
    $rid = $this->createRole(['bypass limited access']);
    $this->restoreReadOnlyConfig();
    $this->account->addRole($rid);
    $this->account->save();

    $this->drupalLogin($this->account);

    // Try to navigate to pages that are not accessible to non-linked users. The
    // users who can bypass limited access should be able to access these pages.
    $this->assertAccess('<front>');
    $this->assertAccess('/collections');
    $this->assertAccess('/solutions');
    $this->assertAccess('/keep-up-to-date');
    $this->assertAccess('/search');

    // Remove the role from account.
    $this->account->removeRole($rid);
    $this->account->save();

    // Create a EU Login user and link it to the local user.
    $authname = $this->randomMachineName();
    $eulogin_pass = user_password();
    $this->createCasUser($authname, "{$authname}@example.com", $eulogin_pass, [], $this->account);

    // The access is allowed.
    $this->casLogin("{$authname}@example.com", $eulogin_pass);
    $this->assertAccess('<front>');
    $this->assertAccess('/collections');
    $this->assertAccess('/solutions');
    $this->assertAccess('/keep-up-to-date');
    $this->assertAccess('/search');
    $this->assertAccess('/contact', 'Contact');
    $this->assertAccess('/user', $this->account->getDisplayName());
    $this->assertAccess($this->account->toUrl('edit-form'), $this->account->getDisplayName());

    // Disable the limited access functionality.
    $this->state->set('joinup_eulogin.disable_limited_access', TRUE);
  }

  /**
   * Tests that special user accounts cannot be linked with EU Login.
   *
   * User accounts intended for maintenance and demonstration purposes (such as
   * UID 1 and demo users) do not represent actual EU citizens so they should
   * not be linked with EU Login. These users have the `bypass limited access`
   * permission.
   */
  public function testLimitedAccessBypassAccounts(): void {
    $this->bypassReadOnlyConfig();
    $local_account = $this->createUser(['bypass limited access']);
    $this->restoreReadOnlyConfig();
    $authname = $this->randomMachineName();
    $pass = $this->randomString();
    $this->createCasUser($authname, "{$authname}@example.com", $pass);
    $this->casLogin("{$authname}@example.com", $pass);

    $page = $this->getSession()->getPage();

    // Select the option that allows pairing with the local account.
    $page->selectFieldOption('account_exist', 'yes');

    // Use the local credentials to pair the account.
    $page->fillField('Email or username', $local_account->getAccountName());
    $page->fillField('Password', $local_account->pass_raw);
    $page->pressButton('Sign in');
    $this->assertSession()->pageTextContains("Linking the local {$local_account->getDisplayName()} user with an EU Login account is not allowed.");
  }

  /**
   * Asserts that navigating to a URL will end on the 'Limited access' page.
   *
   * @param \Drupal\Core\Url|string $url
   *   The URL.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When the test expectations are not met.
   * @throws \Behat\Mink\Exception\ResponseTextException
   *   When the test response expectations are not met.
   */
  protected function assertLimitedAccess($url): void {
    $this->drupalGet($url);
    $assert = $this->assertSession();
    $assert->statusCodeEquals(403);
    $assert->pageTextContains("Dear {$this->account->getDisplayName()}");
    $assert->pageTextContains('Your account access is limited.');
    $assert->pageTextContains('Starting from 02/03/2020, signing in to Joinup is handled by EU Login, the European Commission Authentication Service. After you sign-in using EU Login, you will be able to synchronise your existing Joinup account to restore your access.');
    $assert->linkExists('EU Login');
    $assert->linkByHrefExists('/user/logout?destination=' . base_path() . 'caslogin');
  }

  /**
   * Asserts that navigating to a URL is allowed.
   *
   * @param \Drupal\Core\Url|string $url
   *   The URL.
   * @param string|null $expected_text_string
   *   (optional) String expected to be present in the page text.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When the test expectations are not met.
   * @throws \Behat\Mink\Exception\ResponseTextException
   *   When the test response expectations are not met.
   */
  protected function assertAccess($url, ?string $expected_text_string = NULL): void {
    $this->drupalGet($url);
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    if ($expected_text_string) {
      $assert->pageTextContains($expected_text_string);
    }
  }

  /**
   * Retrieves the password reset link.
   *
   * @return string
   *   The reset URL.
   */
  protected function extractPasswordResetUrlFromMail(): string {
    $mails = $this->getMails();
    $mail = end($mails);
    preg_match('#(/user/reset/[^"].+)"#', (string) $mail['body'], $urls);
    return $urls[1];
  }

  /**
   * Generates a random password with respect to site's password policies.
   *
   * Unfortunately, the Password Policy module lacks a random password generator
   * that would have generate passwords satisfying the site's password policies.
   * It also lacks a password validator service, usable at API level, that would
   * has allowed us to generate such a password. According to Joinup site
   * policies we need a password longer than 8 characters with, at least, three
   * character types, out of four (lowercase letters, uppercase letters, digits,
   * special characters). But user_password() doesn't return special characters,
   * so we will append a random string, that already contains special
   * characters, and borrow the 'character_types' validation, to ensure the
   * password compliance. Not enforcing this leads to random test failures.
   *
   * @see user_password()
   * @see \Drupal\password_policy_character_types\Plugin\PasswordConstraint\CharacterTypes::validate()
   */
  protected function generateRandomPassword(): string {
    // Borrowed from CharacterTypes::validate().
    $valid_password = function (string $password): bool {
      $character_sets = count(array_filter([
        preg_match('/[a-z]/', $password),
        preg_match('/[A-Z]/', $password),
        preg_match('/[0-9]/', $password),
        preg_match('/[^a-zA-Z0-9]/', $password),
      ]));
      return $character_sets >= 3;
    };

    do {
      $password = user_password() . $this->randomString(6);
    } while (!$valid_password($password));

    return $password;
  }

}
