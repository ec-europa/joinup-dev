<?php

/**
 * @file
 * Contains \EuLoginSubContext.
 */

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Drupal\cas\Service\CasUserManager;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\joinup\Traits\UserTrait;
use Drupal\user\Entity\User;

declare(strict_types = 1);

/**
 * Behat step definitions for functionality provided by Joinup EU login module.
 */
class EuLoginSubContext extends DrupalSubContextBase {

  use UserTrait;

  /**
   * A list of field mappings for the authentication map.
   *
   * @var array
   */
  const FIELD_MAPPINGS = [
    'User' => 'uid',
    'External name' => 'authname',
  ];

  /**
   * A list of user ids.
   *
   * @var string[]
   */
  protected $authMaps;

  /**
   * The cas user manager service.
   *
   * @var \Drupal\cas\Service\CasUserManager
   */
  protected $casUserManager;

  /**
   * Creates a number of solutions with data provided in a table.
   *
   * Table format:
   * @codingStandardsIgnoreStart
   * | User | External name |
   * | User name 1 | some_external_user_name_1 |
   * | User name 2 | some_external_user_name_2 |
   * @codingStandardsIgnoreEnd
   *
   * Fields are mandatory..
   *
   * @param \Behat\Gherkin\Node\TableNode $casMappings
   *   The mapping data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect or mandatory values are missing.
   *
   * @Given (the following )cas mappings:
   */
  public function givenAuthMaps(TableNode $casMappings): void {
    foreach ($casMappings->getColumnsHash() as $solution) {
      $values = [];
      // Replace the column aliases with the actual field names.
      foreach ($solution as $key => $value) {
        if (array_key_exists($key, self::FIELD_MAPPINGS)) {
          $values[self::FIELD_MAPPINGS[$key]] = $value;
        }
        else {
          throw new Exception("Unknown column '$key' in solution table.");
        }
      };

      if (empty($values['user']) || empty($values['authname'])) {
        throw new Exception('"User" and "External name" properties are mandatory.');
      }

      $values['user'] = $this->getUserByName($values['uid']);
    }
  }

  /**
   * Generates a mapping for a user account.
   *
   * @param array $data
   *   An array containing the uid, the user object and the authname of the
   *   user.
   */
  protected function createAuthMap($data): void {
    $this->getCasUserManagerService()->setCasUsernameForAccount($data['user'], $data['authname']);
    $this->authMaps[$data['uid']] = $data['uid'];
  }

  /**
   * Remove any created authentication mappings.
   *
   * @AfterScenario
   */
  public function cleanAuthMaps(AfterScenarioScope $event) {
    $cas_user_manager = $this->getCasUserManagerService();
    if (!empty($this->authMaps)) {
      $users = User::loadMultiple($this->authMaps);
      foreach ($users as $user) {
        $cas_user_manager->removeCasUsernameForAccount($user);
      }
    }
  }

  /**
   * Returns the CAS user manager service.
   *
   * @return \Drupal\cas\Service\CasUserManager
   *   The CAS user manager service.
   */
  protected function getCasUserManagerService(): CasUserManager {
    if (empty($this->casUserManager)) {
      $this->casUserManager = \Drupal::service('cas.user_manager');
    }
    return $this->casUserManager;
  }

}
