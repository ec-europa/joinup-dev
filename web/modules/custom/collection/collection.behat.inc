<?php

/**
 * @file
 * Contains \CollectionSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\Entity\Query\Sparql\SparqlArg;
use Drupal\file\Entity\File;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;

/**
 * Behat step definitions for testing collections.
 */
class CollectionSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  /**
   * Test collections.
   *
   * @var \Drupal\rdf_entity\Entity\Rdf[]
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
    $this->visitPath('/rdf_entity/add/collection');
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
    /** @var \Drupal\rdf_entity\Entity\Rdf $collection */
    $collection = $this->getCollectionByName($collection);
    $this->visitPath($collection->url());
  }

  /**
   * Navigates to the edit form of a collection.
   *
   * @param string $collection
   *   The name of the collection.
   *
   * @When (I )go to the :collection collection edit form
   * @When (I )visit the :collection collection edit form
   */
  public function visitEditCollection($collection) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $collection */
    $collection = $this->getCollectionByName($collection);
    $path = $collection->url('edit-form');
    $this->visitPath($path);
  }

  /**
   * Navigates to the collections overview page.
   *
   * @When (I )visit the collection overview page
   */
  public function visitCollectionOverviewPage() {
    $this->visitPath('/collections');
  }

  /**
   * Creates a number of collections with data provided in a table.
   *
   * Table format:
   * @codingStandardsIgnoreStart
   * uri                             | name                    | abstract                                   | access url                             | closed | creation date    | description                                                                                                        | elibrary creation | logo | moderation | modification date | owner |
   * http://joinup.eu/collection/dog | Dog owner collection    | Read up on all about <strong>dogs</strong> | http://dogtime.com/dog-breeds/profiles | 1      | 28-01-1995 12:05 | The Afghan Hound is elegance personified.                                                                          | 0                 |      | 1          |                   |       |
   * http://joinup.eu/collection/cat | Cats collection 4 ever! | Cats are cool!                             | http://mashable.com/category/cats/     | 0      | 28-01-1995 12:06 | The domestic cat (Felis catus or Felis silvestris catus) is a small usually furry domesticated carnivorous mammal. | 1                 |      | 0          |                   |       |
   * @codingStandardsIgnoreEnd
   *
   * Only the name field is required.
   *
   * @param TableNode $collection_table
   *   The collection data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )collections:
   */
  public function givenCollections(TableNode $collection_table) {
    $aliases = self::collectionFieldAliases();

    foreach ($collection_table->getColumnsHash() as $collection) {
      $values = [];
      // Replace the column aliases with the actual field names.
      foreach ($collection as $key => $value) {
        if (array_key_exists($key, $aliases)) {
          $values[$aliases[$key]] = $value;
        }
        else {
          throw new \Exception("Unknown column '$key' in collection table.");
        }
      };
      $this->createCollection($values);
    }
  }

  /**
   * Creates a collection with data provided in a table.
   *
   * Table format:
   * | name            | Open Data Initiative                  |
   * | uri             | http://joinup.eu/collection/odi       |
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
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )collection:
   */
  public function givenCollection(TableNode $collection_table) {
    $aliases = self::collectionFieldAliases();

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
    $this->createCollection($values);
  }

  /**
   * Creates a collection from the given property and field data.
   *
   * @param array $values
   *   An optional associative array of values, keyed by property name.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   A new collection entity.
   *
   * @throws \Exception
   *   Thrown when a given image is not found.
   */
  public function createCollection(array $values) {
    // Add image.
    if (!empty($values['field_ar_logo'])) {
      $filename = $values['field_ar_logo'];
      $files_path = $this->getMinkParameter('files_path');
      $path = rtrim(realpath($files_path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
      if (!is_file($path)) {
        throw new \Exception("Image '$filename' was not found in file path '$files_path'.");
      }
      // Copy the image into the public files folder and turn it into a File
      // entity before linking it to the collection.
      $uri = 'public://' . $filename;
      $destination = file_unmanaged_copy($path, $uri);
      $image = File::create(['uri' => $destination]);
      $image->save();
      $values['field_ar_logo'] = [$image->id()];

      $this->images[$image->id()] = $image;
    }
    // @todo Remove this after the widget in ISAICP-2301 is build.
    $values['rid'] = 'collection';
    $collection = Rdf::create($values);
    $collection->save();
    $this->collections[$collection->id()] = $collection;

    return $collection;
  }

  /**
   * Deletes a collection.
   *
   * @param string $collection
   *   The name of the collection.
   *
   * @When (I )delete the :collection collection
   */
  public function deleteCollection($collection) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $collection */
    $this->getCollectionByName($collection)->delete();
  }

  /**
   * Returns the Collection with the given name.
   *
   * If multiple collections have the same name, the first one will be returned.
   *
   * @param string $name
   *   The collection name.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   The collection.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a collection with the given name does not exist.
   */
  public static function getCollectionByName($name) {
    $query = \Drupal::entityQuery('rdf_entity')
      ->condition('?entity', 'rdf:type', SparqlArg::uri('http://www.w3.org/ns/adms#AssetRepository'))
      ->condition('?entity', SparqlArg::uri('http://purl.org/dc/terms/title'), SparqlArg::literal($name))
      ->range(0, 1);
    $result = $query->execute();

    if (empty($result)) {
      throw new \InvalidArgumentException("The collection with the name '$name' does not exist.");
    }

    return Rdf::load(reset($result));
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
    $actual = \Drupal::entityQuery('rdf_entity')
      ->condition('?entity', 'rdf:type', '<http://www.w3.org/ns/adms#AssetRepository>')
      ->count()
      ->execute();
    if ($actual != $number) {
      throw new \Exception("Wrong number of collections. Expected number: $number, actual number: $actual.");
    }
  }

  /**
   * Checks the number of members in a given collection.
   *
   * In OG parlance a group member can be any kind of entity, but this only
   * checks which Users are members of the collection.
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
      ->condition('entity_type', 'rdf_entity')
      ->condition('entity_id', $collection->id())
      ->condition('state', OgMembershipInterface::STATE_ACTIVE)
      ->count()
      ->execute();

    if ($actual != $number) {
      throw new \Exception("Wrong number of members. Expected number: $number, actual number: $actual.");
    }
  }

  /**
   * Subscribes the given users to the given collections.
   *
   * Table format:
   * | collection               | user          |
   * | Interoperability Friends | Verence II    |
   * | Electronic Surveillance  | Letice Earwig |
   *
   * @param TableNode $membership_table
   *   The membership table.
   *
   * @throws \Exception
   *   Thrown when a collection is not found.
   *
   * @Given (the following )user memberships:
   */
  public function givenUserMemberships(TableNode $membership_table) {
    foreach ($membership_table->getColumnsHash() as $values) {
      // Load group.
      $group = $this->getCollectionByName($values['collection']);
      if (empty($group)) {
        throw new \Exception("Collection " . $values['collection'] . " not found.");
      }

      // Load member.
      $member = user_load_by_name($values['user']);
      if (empty($member)) {
        throw new \Exception("User " . $values['user'] . " not found.");
      }

      /** @var \Drupal\og\Entity\OgMembership $membership */
      $membership = Og::membershipStorage()->create(Og::membershipDefault());
      $membership
        ->setEntityType($group->getEntityTypeId())
        ->setEntityid($group->id())
        ->setState(OgMembershipInterface::STATE_ACTIVE)
        ->setUser($member->id())
        ->save();
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
      // @todo: This delete functionality should occur automatically. See ISAICP-2311.
      $membership_ids = \Drupal::entityQuery('og_membership')
        ->condition('entity_id', $collection->id())
        ->condition('entity_type', 'rdf_entity')
        ->execute();
      $memberships = Og::membershipStorage()->loadMultiple($membership_ids);
      Og::membershipStorage()->delete($memberships);

      // Delete the collection.
      $collection->delete();
    }
    // Also remove the image entities that were attached to the collections.
    foreach ($this->images as $image) {
      $image->delete();
    }
  }

  /**
   * Field alias mapping.
   *
   * @return array
   *    Mapping.
   */
  private static function collectionFieldAliases() {
    // Mapping alias - field name.
    return [
      'uri' => 'id',
      'name' => 'label',
      'abstract' => 'field_ar_abstract',
      'access url' => 'field_ar_access_url',
      'affiliates' => 'field_ar_affiliates',
      'closed' => 'field_ar_closed',
      'contact information' => 'field_ar_contact_information',
      'creation date' => 'field_ar_creation_date',
      'description' => 'field_ar_description',
      'elibrary creation' => 'field_ar_elibrary_creation',
      'logo' => 'field_ar_logo',
      'moderation' => 'field_ar_moderation',
      'modification date' => 'field_ar_modification_date',
      'owner' => 'field_ar_owner',
      'policy domain' => 'field_ar_policy_domain',
      'spatial coverage' => 'field_ar_spatial_coverage',
      'topic' => 'field_ar_topic',
    ];
  }

}
