<?php

namespace Drupal\joinup\Traits;

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

}
