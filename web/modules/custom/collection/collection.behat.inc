<?php

/**
 * @file
 * Contains \CollectionSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\collection\Entity\Collection;
use Drupal\file\Entity\File;
use Drupal\og\OgMembershipInterface;

/**
 * Behat step definitions for testing collections.
 */
class CollectionSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  /**
   * Test collections.
   *
   * @var \Drupal\collection\Entity\Collection[]
   */
  protected $collections = [];

  /**
   * Test images.
   *
   * @var \Drupal\file\Entity\File[]
   */
  protected $images = [];

  /**
   * Navigates to the add collection form.
   *
   * @When (I )go to the add collection form
   * @When (I )visit the add collection form
   */
  public function visitAddCollectionForm() {
    $this->visitPath('/admin/structure/collection/add');
  }

  /**
   * Navigates to the canonical page display of a collection.
   *
   * @param string $collection
   *   The name of the collection.
   *
   * @When (I )go to (the homepage of )the :collection collection
   * @When (I )visit (the homepage of )the :collection collection
   */
  public function visitCollection($collection) {
    $collection = $this->getCollectionByName($collection);
    $this->visitPath('/collection/' . $collection->id());
  }

  /**
   * Creates a collection with data provided in a table.
   *
   * Table format:
   * | name            | Open Data Initiative                  |
   * | author          | Mightily Oats                         |
   * | logo            | logo.png                              |
   * | pre-moderation  | 0|1                                   |
   * | closed          | 0|1                                   |
   * | create elibrary | facilitators|members|registered_users |
   * | schedule        | daily|weekly|monthly                  |
   * | metadata url    | https://ec.europa.eu/my/url           |
   *
   * Only the name field is required.
   *
   * @param TableNode $collection_table
   *   The collection data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect, or a given image is not found.
   *
   * @Given (the following )collection:
   */
  public function givenCollection(TableNode $collection_table) {
    $aliases = [
      'name' => 'name',
      'author' => 'author',
      'logo' => 'field_collection_logo',
      'pre-moderation' => 'field_collection_pre_moderation',
      'closed' => 'field_collection_closed',
      'create elibrary' => 'field_collection_create_elibrary',
      'schedule' => 'field_collection_schedule',
      'metadata url' => 'field_collection_metadata_url',
    ];

    $values = [];
    // Replace the column aliases with the actual field names.
    foreach ($collection_table->getRowsHash() as $key => $value) {
      if (array_key_exists($key, $aliases)) {
        $values[$aliases[$key]] = $value;
      }
      else {
        throw new \Exception("Unknown column '$key' in collection table.");
      }
    };

    // Add image.
    if (!empty($values['field_collection_logo'])) {
      $filename = $values['field_collection_logo'];
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
      $values['field_collection_logo'] = [$image->id()];

      $this->images[$image->id()] = $image;
    }
    $this->createCollection($values);
  }

  /**
   * Creates a new collection with the given values.
   *
   * @param array $values
   *   An optional associative array of values, keyed by property name.
   *
   * @return \Drupal\collection\Entity\Collection
   *   A new collection entity.
   */
  public function createCollection(array $values = []) {
    $collection = Collection::create($values);
    $collection->save();
    $this->collections[$collection->id()] = $collection;

    return $collection;
  }

  /**
   * Returns the Collection with the given name.
   *
   * If multiple collections have the same name, the first one will be returned.
   *
   * @param string $name
   *   The collection name.
   *
   * @return \Drupal\collection\CollectionInterface
   *   The collection.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a collection with the given name does not exist.
   */
  protected function getCollectionByName($name) {
    $result = \Drupal::entityQuery('collection')
      ->condition('name', $name)
      ->range(0, 1)
      ->execute();

    if (empty($result)) {
      throw new \InvalidArgumentException("The collection with the name '$name' does not exist.");
    }

    return Collection::load(reset($result));
  }

  /**
   * Checks the number of available collections.
   *
   * @param int $number
   *   The expected number of collections.
   *
   * @throws \Exception
   *   Thrown when the number of collections does not not match the expectation.
   *
   * @Then I should have :number collection(s)
   */
  public function assertCollectionCount($number) {
    $actual = \Drupal::entityQuery('collection')->count()->execute();
    if ($actual != $number) {
      throw new \Exception("Wrong number of collections. Expected number: $number, actual number: $actual.");
    }
  }

  /**
   * Checks the number of members in a given collection.
   *
   * @param string $collection
   *   The name of the collection to check.
   * @param int $number
   *   The expected number of members in the collection.
   *
   * @throws \Exception
   *   Thrown when the number of members does not not match the expectation.
   *
   * @Then the :collection collection should have :number member(s)
   */
  public function assertMemberCount($collection, $number) {
    $collection = $this->getCollectionByName($collection);
    $actual = \Drupal::entityQuery('og_membership')
      ->condition('group_entity_type', 'collection')
      ->condition('group_entity_id', $collection->id())
      ->condition('state', OgMembershipInterface::STATE_ACTIVE)
      ->count()
      ->execute();

    if ($actual != $number) {
      throw new \Exception("Wrong number of members. Expected number: $number, actual number: $actual.");
    }
  }

  /**
   * Remove any created collections.
   *
   * @AfterScenario
   */
  public function cleanCollections() {
    // Remove any collections that were created.
    foreach ($this->collections as $collection) {
      $collection->delete();
    }
    // Also remove the image entities that were attached to the collections.
    foreach ($this->images as $image) {
      file_unmanaged_delete($image->getFileUri());
      $image->delete();
    }
  }

}
