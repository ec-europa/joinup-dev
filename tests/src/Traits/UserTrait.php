<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\user\UserInterface;

/**
 * Helper methods for dealing with users created in Behat tests.
 */
trait UserTrait {

  /**
   * Mapping of human readable names to machine names.
   *
   * @return array
   *   Field mapping.
   */
  protected static function userFieldAliases() {
    return [
      'Username' => 'name',
      'Password' => 'pass',
      'E-mail' => 'mail',
      'Status' => 'status',
      'Created' => 'created',
      'Roles' => 'roles',
      'First name' => 'field_user_first_name',
      'Family name' => 'field_user_family_name',
      'Photo' => 'field_user_photo',
      'Business title' => 'field_user_business_title',
      'Organisation' => 'field_user_organisation',
      'Nationality' => 'field_user_nationality',
      'Professional domain' => 'field_user_professional_domain',
      'Notification frequency' => 'field_user_frequency',
      // @todo Social network
    ];
  }

  /**
   * Translates human readable field names to machine names.
   *
   * @param array $values
   *   An associative array of field values intended for creating a User entity,
   *   keyed by human readable field names.
   *
   * @return array
   *   The array with the human readable keys translated to machine names.
   *
   * @throws \Exception
   *   Thrown when a human readable key doesn't exist in the list of aliases.
   */
  protected function translateUserFieldAliases(array $values) {
    $translated_values = [];
    $aliases = self::userFieldAliases();
    foreach ($values as $key => $value) {
      if (array_key_exists($key, $aliases)) {
        $translated_values[$aliases[$key]] = $value;
      }
      else {
        throw new \Exception("Unknown column '$key' in user table.");
      }
    }
    return $translated_values;
  }

  /**
   * Creates a user with the given values.
   *
   * @param array $values
   *   An associative array, keyed by field aliases, containing the field values
   *   used to create the user.
   */
  protected function createUser(array $values) {
    // Replace the column aliases with the actual field names.
    $values = $this->translateUserFieldAliases($values);

    // Handle the user profile picture.
    $this->handleFileFields($values, 'user', 'user');

    // Split out roles to process after user is created.
    $roles = [];
    if (isset($values['roles'])) {
      $roles = explode(',', $values['roles']);
      $roles = array_filter(array_map('trim', $roles));
      unset($values['roles']);
    }

    // Provide defaults for required fields.
    if (!isset($values['pass'])) {
      $values['pass'] = $values['name'];
    }
    if (!isset($values['mail'])) {
      $values['mail'] = str_replace(' ', '', $values['name']) . '@example.com';
    }

    $user = (object) $values;
    $this->userCreate($user);

    // Assign roles.
    foreach ($roles as $role) {
      $this->getDriver()->userAddRole($user, $role);
    }
  }

  /**
   * Retrieves a user by name.
   *
   * @param string $name
   *   The name of the user.
   *
   * @return \Drupal\user\UserInterface
   *   The loaded user entity.
   *
   * @throws \RuntimeException
   *   Thrown when a user with the provided name is not found.
   */
  protected function getUserByName($name): UserInterface {
    $user = user_load_by_name($name);

    if (!$user) {
      throw new \RuntimeException("The user with name '$name' was not found.");
    }

    // The user object might be cached, make sure to return it straight from the
    // data storage.
    try {
      /** @var \Drupal\user\UserInterface $user */
      $user = \Drupal::entityTypeManager()->getStorage('user')->loadUnchanged($user->id());
    }
    catch (InvalidPluginDefinitionException $e) {
      throw new \RuntimeException('The user entity storage is not defined.', 0, $e);
    }

    return $user;
  }

}
