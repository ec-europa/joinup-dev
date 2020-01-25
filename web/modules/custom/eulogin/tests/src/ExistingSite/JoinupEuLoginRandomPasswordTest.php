<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_eulogin\ExistingSite;

use Drupal\Tests\joinup_core\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the replacement of password on a successful account linking.
 *
 * @group joinup_eulogin
 */
class JoinupEuLoginRandomPasswordTest extends JoinupExistingSiteTestBase {

  /**
   * Tests the replacement of password on a successful account linking.
   */
  public function test(): void {
    /** @var \Drupal\cas_mock_server\UserManagerInterface $user_manager */
    $user_manager = \Drupal::service('cas_mock_server.user_manager');

    // Create a EU Login user.
    $user_manager->addUser([
      'username' => $authname = $this->randomMachineName(),
      'email' => "{$authname}@example.com",
      'password' => $eulogin_pass = $this->randomString(),
    ]);
    // Create a local user.
    $local_account = $this->createUser();
    // Store the hashed password in a variables.
    $original_hashed_pass = $local_account->getPassword();

    // Login with EU Login.
    $this->drupalGet('/caslogin');
    $page = $this->getSession()->getPage();
    $page->fillField('E-mail address', "{$authname}@example.com");
    $page->fillField('Password', $eulogin_pass);
    $page->pressButton('Log in');

    // Select the option tha allows pairing with the local account.
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

}
