<?php

/**
 * @file
 * Contains \UserSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use \Drupal\user\Entity\User;
use \Drupal\file\Entity\File;

/**
 * Behat step definitions for testing users.
 */
class UserSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  /**
   * Test users.
   *
   * @var \Drupal\user\Entity\User[]
   */
  protected $users = [];

  /**
   * Test images.
   *
   * @var \Drupal\file\Entity\File[]
   */
  protected $images = [];

  /**
   * Creates a user with data provided in a table.
   *
   * The following fields are required:
   *  - Username
   *  - Password
   *  - E-mail
   *  - First name
   *  - Last name.
   *
   * @param TableNode $user_table
   *   The collection data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )user:
   */
  public function givenUser(TableNode $user_table) {
    $values = [];
    $aliases = self::userFieldAliases();
    // Replace the column aliases with the actual field names.
    foreach ($user_table->getRowsHash() as $key => $value) {
      if (array_key_exists($key, $aliases)) {
        $values[$aliases[$key]] = $value;
      }
      else {
        throw new \Exception("Unknown column '$key' in user table.");
      }
    };
    $this->createUser($values);
  }

  /**
   * @Given the following user registration:
   */
  public function submitRegistrationForm(TableNode $table) {
    $this->visitPath('/user/register');

    // Fill in fields.
    foreach ($table->getRowsHash() as $field => $value) {
      $this->getSession()->getPage()->fillField($field, $value);
    };
    // Submit form.
    $this->getSession()->getPage()->pressButton("Create new account");

    // Get the last registered user.
    $storage = \Drupal::entityManager()->getStorage('user');
    $query = $storage->getQuery();
    $result = $query->sort('uid', 'DESC')->range(0, 1)->execute();
    $uid = array_pop($result);
    /** @var \Drupal\user\UserInterface $user */
    $user = $storage->load($uid);

    // Check if latest registered user is created during this request.
    $user_creation_time = $user->getCreatedTime();
    $request_time = \Drupal::requestStack()->getCurrentRequest()->server->get('REQUEST_TIME');
    if (($request_time - $user_creation_time) < 0) {
      // Track user for auto delete in tear-down.
      $this->users[$uid] = $user;
    }
    else {
      throw new Exception('User not registered.');
    }
  }

  /**
   * Mapping of human readable names to machine names.
   *
   * @return array
   *    Field mapping.
   */
  private static function userFieldAliases() {
    return [
      'Username' => 'name',
      'Password' => 'pass',
      'E-mail' => 'mail',
      'Status' => 'status',
      'First name' => 'field_user_first_name',
      'Family name' => 'field_user_family_name',
      'Photo' => 'field_user_photo',
      'Professional profile' => 'field_user_professional_profile',
      'Organisation' => 'field_user_organisation',
      'Nationality' => 'field_user_nationality',
      'Professional domain' => 'field_user_professional_domain',
      // @todo Social network
    ];
  }

  /**
   * Creates a user from the given property and field data.
   *
   * @param array $values
   *   An optional associative array of values, keyed by property name.
   *
   * @return \Drupal\user\Entity\User
   *   A new user entity.
   *
   * @throws \Exception
   *   Thrown when a given image is not found.
   */
  public function createUser(array $values) {
    // Add image.
    if (!empty($values['field_user_photo'])) {
      $filename = $values['field_user_photo'];
      $files_path = $this->getMinkParameter('files_path');
      $path = rtrim(realpath($files_path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
      if (!is_file($path)) {
        throw new \Exception("Image '$filename' was not found in file path '$files_path'.");
      }
      // Copy the image into the public files folder and turn it into a File
      // entity before linking it to the collection.
      $uri = 'public://' . $filename;
      file_unmanaged_copy($path, $uri);
      $image = File::create(['uri' => $uri]);
      $image->save();
      $values['field_user_photo'] = [$image->id()];

      $this->images[$image->id()] = $image;
    }
    $user = User::create($values);
    $user->save();
    $this->users[$user->id()] = $user;

    return $user;
  }

  /**
   * Checks the users existence.
   *
   * @param string $username
   *   The username of the user.
   *
   * @throws \Exception
   *   Thrown when the user is not found.
   *
   * @Then I should have a :username user
   */
  public function assertUserExistence($username) {
    $user = user_load_by_name($username);

    if (empty($user)) {
      throw new \Exception("Unable to load expected user " . $username);
    }
  }

  /**
   * Remove any created users.
   *
   * @AfterScenario
   */
  public function cleanUsers() {
    // Remove any collections that were created.
    foreach ($this->users as $user) {
      $user->delete();
    }
    // Also remove the image entities that were attached to the collections.
    foreach ($this->images as $image) {
      file_unmanaged_delete($image->getFileUri());
      $image->delete();
    }
  }

}
