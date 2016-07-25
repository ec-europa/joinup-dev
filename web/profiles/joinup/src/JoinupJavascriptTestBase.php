<?php

namespace Drupal\joinup;

use Drupal\Core\Database\Database;
use Drupal\Core\Session\AccountInterface;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

/**
 * Base class for Javascript tests for the Joinup platform.
 */
abstract class JoinupJavascriptTestBase extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'joinup';

  /**
   * The entity type manager.
   *
   * @var \Drupal\core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Test users.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $users;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Set up the Sparql database connection.
    $db_url = getenv('SIMPLETEST_SPARQL_DB');
    if (!empty($db_url)) {
      $database = Database::convertDbUrlToConnectionInfo($db_url, DRUPAL_ROOT);
      Database::addConnectionInfo('sparql_default', 'sparql', $database);
    }

    // Clone the current connection and replace the current prefix.
    $connection_info = Database::getConnectionInfo('sparql_default');
    if (is_null($connection_info)) {
      throw new \InvalidArgumentException('There is no Sparql database connection so no tests can be run. You must provide a SIMPLETEST_SPARQL_DB environment variable to run PHPUnit based functional tests outside of run-tests.sh.');
    }
    else {
      Database::renameConnection('sparql_default', 'simpletest_original_sparql_default');
      foreach ($connection_info as $target => $value) {
        // Replace the full table prefix definition to ensure that no table
        // prefixes of the test runner leak into the test.
        $connection_info[$target]['prefix'] = array(
          'default' => $value['prefix']['default'] . $this->databasePrefix,
        );
      }
      Database::addConnectionInfo('sparql_default', 'sparql', $connection_info['sparql']);
    }

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Create test users for each role.
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    foreach ($roles as $role) {
      // Skip the anonymous user.
      if ($role->id() === AccountInterface::ANONYMOUS_ROLE) {
        continue;
      }
      // Use the role name as the user name.
      $username = $role->id();
      $this->users[$username] = $this->drupalCreateUserWithRoles([$role->id(), $username]);
    }
  }

  /**
   * Creates a user with the given role(s).
   *
   * @param array $rids
   *   Array of role IDs to assign to user.
   * @param string $name
   *   (optional) The user name.
   *
   * @return \Drupal\user\Entity\User|false
   *   A fully loaded user object with passRaw property.
   *
   * @throws \Exception
   *   Thrown when the creation of the user fails.
   */
  protected function drupalCreateUserWithRoles(array $rids, $name = NULL) {
    // Remove the 'authenticated' role. It is implied.
    // @see \Drupal\user\Entity\User::preSave()
    if (($key = array_search(RoleInterface::AUTHENTICATED_ID, $rids)) !== FALSE) {
      unset($rids[$key]);
    }

    $edit = array();
    $edit['name'] = !empty($name) ? $name : $this->randomMachineName();
    $edit['mail'] = $edit['name'] . '@example.com';
    $edit['pass'] = user_password();
    $edit['status'] = 1;
    $edit['roles'] = $rids;

    $account = User::create($edit);
    $account->save();

    $this->assertNotNull($account->id());
    if (!$account->id()) {
      throw new \Exception('Could not create user with roles "' . implode(', ', $rids) . '".');
    }

    // Add the raw password so that we can log in as this user.
    $account->passRaw = $edit['pass'];
    return $account;
  }

  /**
   * Todo.
   *
   * @param string $selector
   *   Todo.
   * @param string $label
   *   Todo.
   */
  protected function assertSliderLabelPresent($selector, $label) {
    $wrapper = $this->getSession()->getPage()->find('css', $selector);
    $elements = $wrapper->findAll('css', 'slider__label');
    /** @var \Behat\Mink\Element\NodeElement $element */
    foreach ($elements as $element) {
      $text = $element->getText();
    }
  }

}
