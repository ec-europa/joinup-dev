<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\EntityReferenceTrait;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\FileTrait;
use Drupal\joinup\Traits\FormTrait;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\OgTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\SearchTrait;
use Drupal\joinup\Traits\UserTrait;
use Drupal\joinup\Traits\UtilityTrait;
use Drupal\joinup\Traits\WorkflowTrait;
use Drupal\joinup_collection\JoinupCollectionHelper;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Drupal\og_menu\Tests\Traits\OgMenuTrait;
use Drupal\rdf_entity\RdfInterface;
use Drupal\sparql_entity_storage\UriEncoder;
use Drupal\user\Entity\User;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Behat step definitions for testing collections.
 */
class CollectionContext extends RawDrupalContext {

  use EntityReferenceTrait;
  use EntityTrait;
  use FileTrait;
  use FormTrait;
  use NodeTrait;
  use OgMenuTrait;
  use OgTrait;
  use RdfEntityTrait;
  use SearchTrait;
  use UserTrait;
  use UtilityTrait;
  use WorkflowTrait;

  /**
   * Mapping of human readable field labels to machine names.
   */
  protected const FIELD_ALIASES = [
    'author' => 'uid',
    'uri' => 'id',
    'title' => 'label',
    'short ID' => 'field_short_id',
    'abstract' => 'field_ar_abstract',
    'access url' => 'field_ar_access_url',
    'banner' => 'field_ar_banner',
    'closed' => 'field_ar_closed',
    'contact information' => 'field_ar_contact_information',
    'content creation' => 'field_ar_content_creation',
    'creation date' => 'created',
    'description' => 'field_ar_description',
    'keywords' => 'field_keywords',
    'logo' => 'field_ar_logo',
    'moderation' => 'field_ar_moderation',
    'modification date' => 'changed',
    'owner' => 'field_ar_owner',
    'policy domain' => 'field_policy_domain',
    'spatial coverage' => 'field_spatial_coverage',
    'state' => 'field_ar_state',
    'featured' => 'field_site_featured',
    'pinned to front page' => 'field_site_pinned',
  ];

  /**
   * Test collections.
   *
   * @var \Drupal\rdf_entity\Entity\Rdf[]
   */
  protected $collections = [];

  /**
   * Navigates to the propose collection form.
   *
   * @When I go to the propose collection form
   * @When I visit the propose collection form
   */
  public function visitProposeCollectionForm(): void {
    $this->visitPath('propose/collection');
  }

  /**
   * Navigates to the canonical page display of a collection.
   *
   * @param string $collection
   *   The title of the collection.
   *
   * @When I go to (the homepage of )the :collection collection
   * @When I visit (the homepage of )the :collection collection
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function visitCollection(string $collection): void {
    /** @var \Drupal\rdf_entity\Entity\Rdf $entity */
    $entity = $this->getCollectionByName($collection);
    $this->visitPath($entity->toUrl()->toString());
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
   * @return \Drupal\rdf_entity\RdfInterface
   *   The collection.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a collection with the given title does not exist.
   */
  protected function getCollectionByName(string $title): RdfInterface {
    return $this->getRdfEntityByLabel($title, 'collection');
  }

  /**
   * Navigates to the edit form of a collection.
   *
   * @param string $collection
   *   The title of the collection.
   * @param string $form
   *   The entity form. Either 'edit' or 'delete'.
   *
   * @throws \InvalidArgumentException
   *   If an invalid $form parameter was passed.
   *
   * @When I go to the :collection collection :form form
   * @When I visit the :collection collection :form form
   */
  public function visitCollectionForm(string $collection, string $form): void {
    if (!in_array($form, ['edit', 'delete'])) {
      throw new \InvalidArgumentException('Only "edit" and "delete" are allowed for the $form variable.');
    }
    $collection = $this->getCollectionByName($collection);
    $path = $collection->toUrl("{$form}-form")->getInternalPath();
    $this->visitPath($path);
  }

  /**
   * Navigates to the collections overview page.
   *
   * @todo This is currently dependent on the Joinup profile being installed,
   *   since the view providing this overview page is exported in the profile.
   *   Remove this todo when ISAICP-5176 is fixed.
   *
   * @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-5176
   *
   * @When I visit the collection overview( page)
   */
  public function visitCollectionOverviewPage(): void {
    $this->visitPath('/collections');
  }

  /**
   * Creates a number of collections with data provided in a table.
   *
   * Table format:
   * @codingStandardsIgnoreStart
   * | title                   | abstract                                   | access url                             | closed | creation date    | description                                                                                                        | content creation                      | featured | logo | moderation | modification date | owner | state                                                               |
   * | Dog owner collection    | Read up on all about <strong>dogs</strong> | http://dogtime.com/dog-breeds/profiles | yes|no | 28-01-1995 12:05 | The Afghan Hound is elegance personified.                                                                          | facilitators|members|registered users | yes      |      | yes        |                   |       |                                                                     |
   * | Cats collection 4 ever! | Cats are cool!                             | http://mashable.com/category/cats/     | yes|no | 28-01-1995 12:06 | The domestic cat (Felis catus or Felis silvestris catus) is a small usually furry domesticated carnivorous mammal. | facilitators|members|registered users | no       |      | no         |                   |       | draft|proposed|validated|archival request|deletion request|archived |
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
  public function givenCollections(TableNode $collection_table): void {
    $aliases = self::FIELD_ALIASES;

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
      }

      // Convert user friendly values to machine values.
      $values = $this->convertValueAliases($values);

      // Provide default values.
      $values = $this->provideDefaultValues($values);

      $this->createCollection($values);
    }
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
  protected function provideDefaultValues(array $fields): array {
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
   * @return array
   *   The array with the values converted.
   *
   * @throws \Exception
   *    Throws an exception when a mapped value is not found.
   */
  protected function convertValueAliases(array $fields): array {
    $mapped_values = [
      'field_ar_moderation' => ['no' => 0, 'yes' => 1],
      'field_ar_content_creation' => [
        'facilitators' => 'only_facilitators',
        'members' => 'only_members',
        'registered users' => 'any_user',
      ],
      'field_ar_closed' => ['no' => 0, 'yes' => 1],
      'field_ar_state' => [
        'draft' => 'draft',
        'proposed' => 'proposed',
        'validated' => 'validated',
        'archival request' => 'archival_request',
        'deletion request' => 'deletion_request',
        'archived' => 'archived',
      ],
      'field_site_featured' => ['no' => 0, 'yes' => 1],
      'field_site_pinned' => ['no' => 0, 'yes' => 1],
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
   * @return \Drupal\rdf_entity\RdfInterface
   *   A new collection entity.
   *
   * @throws \Exception
   *   Thrown when a given image is not found.
   */
  protected function createCollection(array $values): RdfInterface {
    // Add images.
    $image_fields = ['field_ar_banner', 'field_ar_logo'];
    foreach ($image_fields as $field_name) {
      if (!empty($values[$field_name])) {
        foreach ($values[$field_name] as &$filename) {
          $filename = [$this->createFile($filename)->id()];
        }
      }
    }

    $collection = $this->createRdfEntity('collection', $values);
    $this->collections[$collection->id()] = $collection;

    // We have to force reindex of affiliated solutions so the relationship
    // with this collection will be indexed in Solr.
    if (!$collection->get('field_ar_affiliates')->isEmpty()) {
      foreach ($collection->get('field_ar_affiliates')->referencedEntities() as $solution) {
        // Reindex the solution, so that the value of the "collection" computed
        // field will be populated and indexed correctly.
        $this->forceSearchApiReindex($solution);
      }
    }

    return $collection;
  }

  /**
   * Creates a collection with data provided in a table.
   *
   * @codingStandardsIgnoreStart
   * Table format:
   * | title            | Open Data Initiative                                                |
   * | author           | Mightily Oats                                                       |
   * | logo             | logo.png                                                            |
   * | featured         | yes|no                                                              |
   * | moderation       | yes|no                                                              |
   * | closed           | yes|no                                                              |
   * | content creation | facilitators|members|registered users                               |
   * | metadata url     | https://ec.europa.eu/my/url                                         |
   * | state            | draft|proposed|validated|archival request|deletion request|archived |
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
  public function givenCollection(TableNode $collection_table): void {
    $aliases = self::FIELD_ALIASES;

    $values = [];
    // Replace the column aliases with the actual field names.
    foreach ($collection_table->getRowsHash() as $key => $value) {
      if (array_key_exists($key, $aliases)) {
        $values[$aliases[$key]] = $value;
      }
      else {
        throw new \Exception("Unknown column '$key' in collection table.");
      }
    }

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
   * @When I delete the :collection collection
   */
  public function deleteCollection(string $collection): void {
    $collection = $this->getCollectionByName($collection);
    $collection->skip_notification = TRUE;
    $collection->delete();
  }

  /**
   * Checks the number of available collections.
   *
   * @param int $number
   *   The expected number of collections.
   *
   * @Then I should have :number collection(s)
   */
  public function assertCollectionCount(int $number): void {
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
   * @param string $membership_state
   *   The state of the membership. Can be either active, pending or blocked.
   *
   * @throws \Exception
   *   Thrown when the number of members does not not match the expectation.
   *
   * @Then the :collection collection should have :number :membership_state member(s)
   */
  public function assertMemberCount(string $collection, int $number, string $membership_state): void {
    $states = [
      OgMembershipInterface::STATE_ACTIVE,
      OgMembershipInterface::STATE_PENDING,
      OgMembershipInterface::STATE_BLOCKED,
    ];

    if (!in_array($membership_state, $states)) {
      throw new \Exception("Invalid membership state '{$membership_state}' found.");
    }

    $collection = $this->getCollectionByName($collection);
    $actual = \Drupal::entityQuery('og_membership')
      ->condition('entity_type', 'rdf_entity')
      ->condition('entity_id', $collection->id())
      ->condition('state', $membership_state)
      ->count()
      ->execute();

    if ($actual != $number) {
      throw new \Exception("Wrong number of {$membership_state} members. Expected number: $number, actual number: $actual.");
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
  public function givenCollectionUserMemberships(TableNode $membership_table): void {
    foreach ($membership_table->getColumnsHash() as $values) {
      $group = $this->getCollectionByName($values['collection']);
      $this->givenUserMembership($group, $values);
    }
  }

  /**
   * Alters custom page menu items for group navigation menus.
   *
   * It does not create the menu items as they are created automatically when
   * the custom pages are created.
   *
   * Table format:
   * | title              | parent              | weight | enabled |
   * | Custom page parent | Custom page child 1 |      1 | yes     |
   * | Custom page parent | Custom page child 2 |      3 | yes     |
   * | Custom page parent | Custom page child 3 |      2 | no      |
   *
   * All columns except the 'title' column are optional.
   *
   * @param \Behat\Gherkin\Node\TableNode $menu_table
   *   The menu table.
   *
   * @throws \Exception
   *    Throws an exception if the parent item is not found.
   *
   * @Given (the following )custom page(s) menu structure:
   * @Given (the following )collection menu structure:
   */
  public function givenCollectionMenuStructure(TableNode $menu_table): void {
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    foreach ($menu_table->getColumnsHash() as $values) {
      if (!empty($values['parent'])) {
        $parent_link = $this->getMenuLinkByTitle($values['parent']);
        $values['parent'] = $parent_link->getPluginId();
      }

      if (!empty($values['enabled'])) {
        $values['enabled'] = (int) ($values['enabled'] === 'yes');
      }

      $child_link = $this->getMenuLinkByTitle($values['title']);
      $menu_link_manager->updateDefinition($child_link->getPluginId(), $values);
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
  public function assertCollectionOwnership(string $username, string $rdf_entity): void {
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
  public function assertCollectionOwnershipCurrentUser(string $rdf_entity): void {
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
  public function cleanCollections(): void {
    if (empty($this->collections)) {
      return;
    }

    // Since we might be cleaning up many collections, temporarily disable the
    // feature to commit the index after every query.
    $this->disableCommitOnUpdate();

    // Remove any collections that were created.
    foreach ($this->collections as $collection) {
      $collection->skip_notification = TRUE;
      $collection->delete();
    }
    $this->collections = [];
    $this->enableCommitOnUpdate();
  }

  /**
   * Checks that a user has the available state options for the collection.
   *
   * The method also checks that these options are the only options available.
   * This method will log in as each user in sequence, so take care to only use
   * it when the currently logged in user can be discarded.
   *
   * Table format:
   * | collection   | user | buttons         |
   * | Collection A | John | Save as draft   |
   * | Collection B | Jack | Update, Publish |
   *
   * @param \Behat\Gherkin\Node\TableNode $check_table
   *   The table with the triplets collection-user-buttons.
   *
   * @throws \Exception
   *    Thrown when the user does not exist.
   *
   * @todo: Maybe there is a better definition available here like 'The
   * following state buttons should be available for the user on the
   * collection'.
   *
   * @Then for the following collection, the corresponding user should have the corresponding (available )state buttons:
   */
  public function verifyStateButtons(TableNode $check_table): void {
    foreach ($check_table->getColumnsHash() as $values) {
      $username = $values['user'];
      $user = $this->userManager->getUser($username);

      // Check if we are already logged in as the user to test.
      $current_username = $this->userManager->getCurrentUser()->name ?? NULL;
      if ($username !== $current_username) {
        $this->authenticationManager->logIn($user);
      }

      // Go to the edit form and check that the expected buttons are visible.
      $this->visitCollectionForm($values['collection'], 'edit');
      $buttons = $this->explodeCommaSeparatedStepArgument($values['buttons']);
      $this->assertSubmitButtonsVisible($buttons);
    }
  }

  /**
   * Navigates to the collection leave confirmation form.
   *
   * @param string $label
   *   The label of the collection group.
   *
   * @Given I am about to leave( the) :label( collection)
   */
  public function visitCollectionLeaveConfirmationPage(string $label): void {
    $collection = $this->getEntityByLabel('rdf_entity', $label, 'collection');
    $encoded_id = UriEncoder::encodeUrl($collection->id());
    $this->visitPath("/rdf_entity/$encoded_id/leave");
  }

  /**
   * Asserts that a given or current user is member of collection list.
   *
   * @param string $labels
   *   A string of comma separated labels.
   * @param string|null $user_name
   *   The user name related to the assertion.
   *
   * @throws \Exception
   *   Thrown if a logged in user was not found or it was not passed, or if the
   *   assertion failed.
   *
   * @Then I am member of :labels collection(s)
   * @Then user :user_name is member of :labels collection(s)
   */
  public function assertUserIsMemberOfCollection(string $labels, ?string $user_name = NULL): void {
    /** @var \Drupal\user\UserInterface $account */
    if (!$user_name) {
      $user = $this->getUserManager()->getCurrentUser();
      $account = User::load($user->uid);
      if ($account->isAnonymous()) {
        throw new \Exception("No current logged in user.");
      }
    }
    else {
      $account = $this->getUserByName($user_name);
    }

    $failures = [];
    foreach ($this->explodeCommaSeparatedStepArgument($labels) as $label) {
      $collection = $this->getEntityByLabel('rdf_entity', $label, 'collection');
      if (!Og::getMembership($collection, $account, [])) {
        $failures[] = $label;
      }
    }

    if ($failures) {
      throw new \Exception("User $user_name is not member of the next collections: '" . implode("', '", $failures) . "'");
    }
  }

  /**
   * Updates the name of a collection.
   *
   * @param string $collection
   *   The name of the collection to update.
   * @param string $name
   *   The new anem for the collection.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when the updated collection cannot be saved.
   *
   * @When I change the name of the :collection collection to :name
   */
  public function updateCollectionName(string $collection, string $name): void {
    $entity = $this->getCollectionByName($collection);
    $entity->setName($name)->save();
  }

  /**
   * Creates the standard 'Joinup' collection.
   *
   * @beforeScenario @joinup_collection
   *
   * @see joinup_collection.module
   */
  public function createJoinupCollection(): void {
    $this->createCollection([
      'id' => JoinupCollectionHelper::getCollectionId(),
      'label' => 'Joinup',
      'field_ar_state' => 'validated',
    ]);
  }

  /**
   * Asserts that a glossary navigator is present or not on the page.
   *
   * @param string|null $expected_navigator
   *   The navigator links.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   The navigator is missing from the page.
   *
   * @Then I should see the glossary navigator :expected_navigator
   * @Then I should not see the glossary navigator
   */
  public function assertGlossaryNavigator(?string $expected_navigator = NULL): void {
    $page = $this->getSession()->getPage();
    $navigator_node = $page->find('css', '.glossary-navigator');

    if (!$expected_navigator) {
      if ($navigator_node) {
        throw new ExpectationFailedException("The glossary navigator exists on the page but it should not.");
      }
      return;
    }

    if (!$navigator_node) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'navigator');
    }

    $actual_navigator = $navigator_node->getText();
    if ($actual_navigator !== $expected_navigator) {
      throw new ExpectationFailedException("Expected navigator '{$expected_navigator}' but found '{$actual_navigator}'.");
    }
  }

}
