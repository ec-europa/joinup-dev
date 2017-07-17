<?php

/**
 * @file
 * Contains \CollectionSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\EntityReferenceTrait;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\FileTrait;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\OgTrait;
use Drupal\joinup\Traits\RandomGeneratorTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\WorkflowTrait;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Drupal\og_menu\Tests\Traits\OgMenuTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Behat step definitions for testing collections.
 */
class CollectionSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use EntityReferenceTrait;
  use EntityTrait;
  use FileTrait;
  use NodeTrait;
  use OgMenuTrait;
  use OgTrait;
  use RandomGeneratorTrait;
  use RdfEntityTrait;
  use WorkflowTrait;

  /**
   * Test collections.
   *
   * @var \Drupal\rdf_entity\Entity\Rdf[]
   */
  protected $collections = [];

  /**
   * Navigates to the propose collection form.
   *
   * @When (I )go to the propose collection form
   * @When (I )visit the propose collection form
   */
  public function visitProposeCollectionForm() {
    $this->visitPath('propose/collection');
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
   * Asserts the order of elements.
   *
   * @Then I should see the following collection menu items in the specified order:
   */
  public function assertRepeatedElementContainsText(TableNode $table) {
    $parent = $this->getSession()->getPage()->findAll('css', '.block-collection-menu-blocknavigation li.sidebar-menu__item');
    $i = 0;
    foreach ($table->getHash() as $repeatedElement) {
      $child = $parent[$i];
      \PHPUnit_Framework_Assert::assertEquals(
        $child->find('css', 'a.sidebar-menu__link')->getText(),
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
    $collection = $this->getRdfEntityByLabel($title, 'collection');
    return $collection;
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
   * | title                   | abstract                                   | access url                             | closed | creation date    | description                                                                                                        | elibrary creation                     | logo | moderation | modification date | owner | state                                                               |
   * | Dog owner collection    | Read up on all about <strong>dogs</strong> | http://dogtime.com/dog-breeds/profiles | yes|no | 28-01-1995 12:05 | The Afghan Hound is elegance personified.                                                                          | facilitators|members|registered users |      | yes        |                   |       |                                                                     |
   * | Cats collection 4 ever! | Cats are cool!                             | http://mashable.com/category/cats/     | yes|no | 28-01-1995 12:06 | The domestic cat (Felis catus or Felis silvestris catus) is a small usually furry domesticated carnivorous mammal. | facilitators|members|registered users |      | no         |                   |       | draft|proposed|validated|archival request|deletion request|archived |
   * @codingStandardsIgnoreEnd
   *
   * Only the title field is required.
   *
   * @param \Behat\Gherkin\Node\TableNode $collection_table
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

      // Provide default values.
      $values = $this->provideDefaultValues($values);

      $this->createCollection($values);
    }
  }

  /**
   * Returns the field alias mapping.
   *
   * @return array
   *   The field alias mapping.
   */
  protected static function collectionFieldAliases() {
    // Mapping alias - field name.
    return [
      'uri' => 'id',
      'title' => 'label',
      'abstract' => 'field_ar_abstract',
      'access url' => 'field_ar_access_url',
      'affiliates' => 'field_ar_affiliates',
      'banner' => 'field_ar_banner',
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
      'spatial coverage' => 'field_spatial_coverage',
      'state' => 'field_ar_state',
    ];
  }

  /**
   * Enriches the provided field values with defaults for missing values.
   *
   * @param array $fields
   *   An array of fields keyed by field name.
   *
   * @return array
   *   The array with default values added.
   */
  protected function provideDefaultValues(array $fields) {
    $default_values = [
      'field_ar_state' => 'draft',
    ];

    foreach ($default_values as $field => $default_value) {
      if (!isset($fields[$field])) {
        $fields[$field] = $default_value;
      }
    }

    return $fields;
  }

  /**
   * Converts values from user friendly to normal machine values.
   *
   * @param array $fields
   *   An array of fields keyed by field name.
   *
   * @return mixed
   *   The array with the values converted.
   *
   * @throws \Exception
   *    Throws an exception when a mapped value is not found.
   */
  protected function convertValueAliases(array $fields) {
    $mapped_values = [
      'field_ar_moderation' => ['no' => 0, 'yes' => 1],
      'field_ar_elibrary_creation' =>
        ['facilitators' => 0, 'members' => 1, 'registered users' => 2],
      'field_ar_closed' => ['no' => 0, 'yes' => 1],
      'field_ar_state' => [
        'draft' => 'draft',
        'proposed' => 'proposed',
        'validated' => 'validated',
        'archival request' => 'archival_request',
        'deletion request' => 'deletion_request',
        'archived' => 'archived',
      ],
    ];

    foreach ($fields as $field => $value) {
      if (isset($mapped_values[$field])) {
        if (!isset($mapped_values[$field][$value])) {
          throw new \Exception("Value $value is not an acceptable value for field $field.");
        }

        $fields[$field] = $mapped_values[$field][$value];
      }
    }

    // Convert any entity reference field label value with the entity id.
    $fields = $this->convertEntityReferencesValues('rdf_entity', 'collection', $this->parseRdfEntityFields($fields));

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
    // Add images.
    $image_fields = ['field_ar_banner', 'field_ar_logo'];
    foreach ($image_fields as $field_name) {
      if (!empty($values[$field_name])) {
        foreach ($values[$field_name] as &$filename) {
          $filename = [$this->createFile($filename)->id()];
        }
      }
    }

    $values['rid'] = 'collection';
    $collection = Rdf::create($values);
    $collection->save();
    $this->collections[$collection->id()] = $collection;

    // We have to force reindex of affiliated solutions so the relationship
    // with this collection will be indexed in Solr.
    if (!$collection->get('field_ar_affiliates')->isEmpty()) {
      foreach ($collection->get('field_ar_affiliates')->referencedEntities() as $solution) {
        // Reset the cached entity in the storage, so the "collection" computed
        // field will be populated correctly when the solution gets loaded.
        \Drupal::entityTypeManager()->getStorage('rdf_entity')->resetCache([$solution->id()]);
        // In order to avoid copying code from search_api_entity_update(), we
        // need to fake an update event. Said function requires the "original"
        // property to be populated, so just fill it with the entity itself.
        $solution->original = $solution;
        search_api_entity_update($solution);
      }
    }

    return $collection;
  }

  /**
   * Creates a collection with data provided in a table.
   *
   * @codingStandardsIgnoreStart
   * Table format:
   * | title           | Open Data Initiative                                                |
   * | author          | Mightily Oats                                                       |
   * | logo            | logo.png                                                            |
   * | moderation      | yes|no                                                              |
   * | closed          | yes|no                                                              |
   * | create elibrary | facilitators|members|registered users                               |
   * | metadata url    | https://ec.europa.eu/my/url                                         |
   * | state           | draft|proposed|validated|archival request|deletion request|archived |
   * @codingStandardsIgnoreEnd
   *
   * Only the title field is required.
   *
   * @param \Behat\Gherkin\Node\TableNode $collection_table
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

    // Provide default values.
    $values = $this->provideDefaultValues($values);

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
      ->condition('state', [
        OgMembershipInterface::STATE_ACTIVE,
        OgMembershipInterface::STATE_PENDING,
      ], 'IN')
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
   * @codingStandardsIgnoreStart
   * | collection               | user          | roles                      | state   |
   * | Interoperability Friends | Verence II    | facilitator, administrator | active  |
   * | Electronic Surveillance  | Letice Earwig |                            | blocked |
   * @codingStandardsIgnoreEnd
   *
   * Only the 'collection' and 'user' columns are required. Multiple comma
   * separated roles can be passed.
   *
   * The state can be empty, or one of 'active', 'pending', or 'blocked'. If the
   * state is omitted it will default to 'active'.
   *
   * @param \Behat\Gherkin\Node\TableNode $membership_table
   *   The membership table.
   *
   * @throws \Exception
   *   Thrown when a collection is not found.
   *
   * @Given (the following )collection user membership(s):
   */
  public function givenCollectionUserMemberships(TableNode $membership_table) {
    foreach ($membership_table->getColumnsHash() as $values) {
      // Load group.
      $group = $this->getCollectionByName($values['collection']);

      // Load member.
      $member = user_load_by_name($values['user']);
      if (empty($member)) {
        throw new \Exception("User " . $values['user'] . " not found.");
      }

      // Convert role names to role IDs.
      $roles = [];
      if (!empty($values['roles'])) {
        $role_names = explode(',', $values['roles']);
        $role_names = array_map('trim', $role_names);
        // Every owner is also a facilitator. In Joinup the facilitator role is
        // assigned to the owner when they create the collection. Since in this
        // step the collections are already created, mimick this behaviour by
        // making sure every owner also has the 'facilitator' role.
        if (in_array('owner', $role_names) && !in_array('facilitator', $role_names)) {
          $role_names[] = 'facilitator';
        }
        $roles = $this->getOgRoles($role_names, $group);
      }

      $state = !empty($values['state']) ? $values['state'] : NULL;

      $this->subscribeUserToGroup($member, $group, $roles, $state);
    }
  }

  /**
   * Creates menu items for an og group menu.
   *
   * This function uses only the navigation menu for the items.
   * If the collection passed does not have a menu yet, one is created.
   * Nodes are loaded by title.
   *
   * Table format:
   * | collection   | label             | page         | weight |
   * | Collection 1 | Page 1 menu label | Page 1 title | 0      |
   * | Collection 1 | Page 2 menu label | Page 2 title | 1      |
   * | Collection 2 | Page 3 menu label | Page 3 title | 0      |
   *
   * @param string $bundle
   *   The bundle of the content entity.
   * @param \Behat\Gherkin\Node\TableNode $menu_table
   *   The menu table.
   *
   * @Given (the following ):bundle content menu items for the corresponding collections:
   */
  public function givenCollectionMenuItems($bundle, TableNode $menu_table) {
    foreach ($menu_table->getColumnsHash() as $values) {
      $group = $this->getRdfEntityByLabel($values['collection'], 'collection');
      $content = $this->getNodeByTitle($values['page'], $bundle);
      $og_menu_instance = $this->getOgMenuInstance($group->id(), 'navigation');
      if (empty($og_menu_instance)) {
        $og_menu_instance = $this->createOgMenuInstance($group->id(), 'navigation');
      }

      // Prepare the array to pass to MenuLinkContent::create method.
      $menu_item_data = [
        'title' => $values['label'],
        'link' => ['uri' => 'internal:/node/' . $content->id()],
        'menu_name' => 'ogmenu-' . $og_menu_instance->id(),
        'weight' => $values['weight'],
        'expanded' => TRUE,
      ];

      $this->createOgMenuItem($menu_item_data);
    }
  }

  /**
   * Alters relationships between menu items for the custom pages menu.
   *
   * It does not create the menu items as they are created automatically when
   * the custom pages are created.
   *
   * Table format:
   * | title              | parent              | weight |
   * | Custom page parent | Custom page child 1 |      1 |
   * | Custom page parent | Custom page child 2 |      3 |
   * | Custom page parent | Custom page child 3 |      2 |
   *
   * @param \Behat\Gherkin\Node\TableNode $menu_table
   *   The menu table.
   *
   * @throws \Exception
   *    Throws an exception if the parent item is not found.
   *
   * @Given (the following )custom page(s) menu structure:
   */
  public function givenCollectionMenuStructure(TableNode $menu_table) {
    foreach ($menu_table->getColumnsHash() as $values) {
      $child_link = $this->getMenuLinkByTitle($values['title']);
      $child_link->set('weight', $values['weight']);
      if (!empty($values['parent'])) {
        $parent_link = $this->getMenuLinkByTitle($values['parent']);
        $child_link->set('parent', $parent_link->getPluginId());
      }
      $child_link->save();
    }
  }

  /**
   * Asserts that a user is an owner of the given collection.
   *
   * To be owner of a collection, a user should be an administrator,
   * a facilitator and a member.
   *
   * @param string $username
   *   The name of the user.
   * @param string $rdf_entity
   *   The label of the group entity.
   *
   * @throws \Exception
   *    Throws an exception when the user is not found.
   *
   * @Given (the user ):username should be the owner of the :rdf_entity collection
   */
  public function assertCollectionOwnership($username, $rdf_entity) {
    $user = user_load_by_name($username);
    if (empty($user)) {
      throw new \Exception("User {$username} could not be found.");
    }

    $collection = $this->getRdfEntityByLabel($rdf_entity, 'collection');
    $owner_roles = [
      OgRoleInterface::ADMINISTRATOR,
      OgRoleInterface::AUTHENTICATED,
      'facilitator',
    ];

    $this->assertOgGroupOwnership($user, $collection, $owner_roles);
  }

  /**
   * Asserts that the current user is an owner of the given collection.
   *
   * @param string $rdf_entity
   *   The label of the group entity.
   *
   * @throws \Exception
   *    Throws an exception when the user is not found.
   *
   * @see assertCollectionOwnership()
   *
   * @Given I should own the :rdf_entity collection
   */
  public function assertCollectionOwnershipCurrentUser($rdf_entity) {
    if (!$current_user = $this->userManager->getCurrentUser()) {
      throw new \Exception("No current user.");
    }
    $this->assertCollectionOwnership($current_user->name, $rdf_entity);
  }

  /**
   * Removes any created collections.
   *
   * @AfterScenario
   */
  public function cleanCollections() {
    // Remove any collections that were created.
    foreach ($this->collections as $collection) {
      $collection->delete();
    }
  }

  /**
   * Checks that a user has the available state options for the collection.
   *
   * The method also checks that these options are the only options available.
   *
   * Table format:
   * | collection   | user | options          |
   * | Collection A | John | Status1          |
   * | Collection B | Jack | Status1, Status2 |
   *
   * @param \Behat\Gherkin\Node\TableNode $check_table
   *   The table with the triplets collection-user-options.
   *
   * @throws \Exception
   *    Thrown when the user does not exist.
   *
   * @todo: Maybe there is a better definition available here like 'The
   * following state buttons should be available for the user on the
   * collection'.
   *
   * @Given for the following collection, the corresponding user should have the corresponding (available )state buttons:
   */
  public function verifyWorkflowTransitions(TableNode $check_table) {
    foreach ($check_table->getColumnsHash() as $values) {
      // Load entity.
      $collection = $this->getCollectionByName($values['collection']);

      // Load user.
      $user = user_load_by_name($values['user']);
      if (empty($user)) {
        throw new \Exception('User ' . $values['user'] . ' not found.');
      }

      // Convert states to an array.
      $states = empty($values['states']) ? [] : explode(',', $values['states']);
      $states = array_map('trim', $states);

      $this->assertAvailableTransitions($collection, $states, $user);
    }
  }

  /**
   * Applies a transition to a collection.
   *
   * @param string $username
   *   The username applying the transition.
   * @param string $entity_name
   *   The title of the collection.
   * @param string $to_state
   *   The state that the collection transits to.
   *
   * @throws \Exception
   *    An exception is thrown if the user is not found or the transition is not
   *    allowed for this user.
   *
   * @Given the user :username changes the state of the :entity_name collection to :to_state
   */
  public function applyCollectionWorkflowTransition($username, $entity_name, $to_state) {
    $user = user_load_by_name($username);
    if (empty($user)) {
      throw new \Exception("User {$username} was not found.");
    }
    $collection = $this->getCollectionByName($entity_name);
    $available_states = $this->getAvailableStates($collection, $user);
    if (($transition_id = array_search($to_state, $available_states)) === FALSE) {
      throw new \Exception("The given user does not have permission to apply the specific transition.");
    }
    $transition = $collection->get('field_ar_state')->first()->getWorkflow()->getTransition($transition_id);
    $collection->get('field_ar_state')->first()->applyTransition($transition);
    $collection->save();
  }

  /**
   * Asserts that the string "x days ago" is present on the page.
   *
   * @Then I see the days passed since :date
   */
  public function assertDaysPassedSince($date) {
    $timestamp = strtotime($date);
    $seconds_since = time() - $timestamp;
    $days_since = floor($seconds_since / 86400);
    $text = sprintf("%s days ago", $days_since);
    $this->assertSession()->pageTextContains($text);
  }

}
