<?php

/**
 * @file
 * Contains \CollectionSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\FileTrait;
use Drupal\joinup\Traits\RandomGeneratorTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Behat step definitions for testing collections.
 */
class CollectionSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use FileTrait;
  use RandomGeneratorTrait;
  use RdfEntityTrait;

  /**
   * Test collections.
   *
   * @var \Drupal\rdf_entity\Entity\Rdf[]
   */
  protected $collections = [];

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
   *   The title of the collection.
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
   * Assert the order of elements.
   *
   * @Then I should see the following collection menu items in the specified order:
   */
  public function assertRepeatedElementContainsText(TableNode $table) {
    $parent = $this->getSession()->getPage()->findAll('css', '.block-og-menu ul.menu .menu-item');
    $i = 0;
    foreach ($table->getHash() as $repeatedElement) {
      $child = $parent[$i];
      \PHPUnit_Framework_Assert::assertEquals(
        $child->find('css', '.menu-item')->getText(),
        $repeatedElement['text']
      );
      $i++;
    }
  }

  /**
   * Returns the Collection with the given title.
   *
   * If multiple collections have the same title,
   * the first one will be returned.
   *
   * @param string $title
   *   The collection title.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   The collection.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a collection with the given title does not exist.
   */
  protected function getCollectionByName($title) {
    return $this->getRdfEntityByLabel($title, 'collection');
  }

  /**
   * Navigates to the edit form of a collection.
   *
   * @param string $collection
   *   The title of the collection.
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
   * title                   | abstract                                   | access url                             | closed | creation date    | description                                                                                                        | elibrary creation                     | logo | moderation | modification date | owner |
   * Dog owner collection    | Read up on all about <strong>dogs</strong> | http://dogtime.com/dog-breeds/profiles | yes|no | 28-01-1995 12:05 | The Afghan Hound is elegance personified.                                                                          | facilitators|members|registered users |      | yes        |                   |       |
   * Cats collection 4 ever! | Cats are cool!                             | http://mashable.com/category/cats/     | yes|no | 28-01-1995 12:06 | The domestic cat (Felis catus or Felis silvestris catus) is a small usually furry domesticated carnivorous mammal. | facilitators|members|registered users |      | no         |                   |       |
   * @codingStandardsIgnoreEnd
   *
   * Only the title field is required.
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

      // Convert user friendly values to machine values.
      $values = $this->convertValueAliases($values);

      $this->createCollection($values);
    }
  }

  /**
   * Field alias mapping.
   *
   * @return array
   *    Mapping.
   */
  protected static function collectionFieldAliases() {
    // Mapping alias - field name.
    return [
      'uri' => 'id',
      'title' => 'label',
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
      'policy domain' => 'field_policy_domain',
      'spatial coverage' => 'field_spacial_coverage',
      'topic' => 'field_topic',
    ];
  }

  /**
   * Converts values from user friendly to normal machine values.
   *
   * @param array $fields
   *    An array of fields keyed by field name.
   *
   * @return mixed
   *    The array with the values converted.
   *
   * @throws \Exception
   *    Throws an exception when a mapped value is not found.
   */
  protected function convertValueAliases($fields) {
    $mapped_values = [
      'field_ar_moderation' => ['no' => 0, 'yes' => 1],
      'field_ar_elibrary_creation' =>
        ['facilitators' => 0, 'members' => 1, 'registered users' => 2],
      'field_ar_closed' => ['no' => 0, 'yes' => 1],
    ];

    foreach ($fields as $field => $value) {
      if (isset($mapped_values[$field])) {
        if (!isset($mapped_values[$field][$value])) {
          throw new \Exception("Value $value is not an acceptable value for field $field.");
        }

        $fields[$field] = $mapped_values[$field][$value];
      }
    }

    return $fields;
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
  protected function createCollection(array $values) {
    // Add image.
    if (!empty($values['field_ar_logo'])) {
      $values['field_ar_logo'] = [$this->createFile($values['field_ar_logo'], $this->getMinkParameter('files_path'))];
    }

    // @todo Remove this after the widget in ISAICP-2301 is build.
    $values['rid'] = 'collection';
    $values['id'] = isset($values['id']) ? $values['id'] : $this->getRandomUri();
    $collection = Rdf::create($values);
    $collection->save();
    $this->collections[$collection->id()] = $collection;

    return $collection;
  }

  /**
   * Creates a collection with data provided in a table.
   *
   * Table format:
   * | title           | Open Data Initiative                  |
   * | author          | Mightily Oats                         |
   * | logo            | logo.png                              |
   * | moderation      | yes|no                                |
   * | closed          | yes|no                                |
   * | create elibrary | facilitators|members|registered users |
   * | metadata url    | https://ec.europa.eu/my/url           |
   *
   * Only the title field is required.
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

    // Convert user friendly values to machine values.
    $values = $this->convertValueAliases($values);

    $this->createCollection($values);
  }

  /**
   * Deletes a collection.
   *
   * @param string $collection
   *   The title of the collection.
   *
   * @When (I )delete the :collection collection
   */
  public function deleteCollection($collection) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $collection */
    $this->getCollectionByName($collection)->delete();
  }

  /**
   * Checks the number of available collections.
   *
   * @param int $number
   *   The expected number of collections.
   *
   * @Then I should have :number collection(s)
   */
  public function assertCollectionCount($number) {
    $this->assertRdfEntityCount($number, 'collection');
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
   * | collection               | user          | roles                      |
   * | Interoperability Friends | Verence II    | facilitator, administrator |
   * | Electronic Surveillance  | Letice Earwig |                            |
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
        ->setGroupEntityType($group->getEntityTypeId())
        ->setEntityid($group->id())
        ->setState(OgMembershipInterface::STATE_ACTIVE)
        ->setUser($member->id());

      if (!empty($values['roles'])) {
        $roles = explode(',', $values['roles']);
        foreach ($roles as $role) {
          // The role ID is comprised of the entity type ID, bundle ID and role
          // name, separated by dashes.
          $role_id = 'rdf_entity-collection-' . trim($role);
          $membership->addRole($role_id);
        }
      }
      $membership->save();
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
  }

}
