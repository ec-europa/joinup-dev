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
use Drupal\joinup_collection\JoinupCommunityHelper;
use Drupal\joinup_group\ContentCreationOptions;
use Drupal\og\OgRoleInterface;
use Drupal\og_menu\Tests\Traits\OgMenuTrait;
use Drupal\rdf_entity\RdfInterface;
use Drupal\sparql_entity_storage\UriEncoder;
use Drupal\user\Entity\User;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Behat step definitions for testing communities.
 */
class CommunityContext extends RawDrupalContext {

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
    'topic' => 'field_topic',
    'spatial coverage' => 'field_spatial_coverage',
    'state' => 'field_ar_state',
    'featured' => 'feature',
  ];

  /**
   * Test communities.
   *
   * @var \Drupal\rdf_entity\Entity\Rdf[]
   */
  protected $communities = [];

  /**
   * Navigates to the propose community form.
   *
   * @When I go to the propose community form
   * @When I visit the propose community form
   */
  public function visitProposeCommunityForm(): void {
    $this->visitPath('propose/collection');
  }

  /**
   * Navigates to the canonical page display of a community.
   *
   * @param string $community
   *   The title of the community.
   *
   * @When I go to (the homepage of )the :community community
   * @When I visit (the homepage of )the :community community
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function visitCommunity(string $community): void {
    /** @var \Drupal\rdf_entity\Entity\Rdf $entity */
    $entity = $this->getCommunityByName($community);
    $this->visitPath($entity->toUrl()->toString());
  }

  /**
   * Returns the Community with the given title.
   *
   * If multiple communities have the same title,
   * the first one will be returned.
   *
   * @param string $title
   *   The community title.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The community.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a community with the given title does not exist.
   */
  protected function getCommunityByName(string $title): RdfInterface {
    return $this->getRdfEntityByLabel($title, 'collection');
  }

  /**
   * Navigates to the communities overview page.
   *
   * @todo This is currently dependent on the Joinup profile being installed,
   *   since the view providing this overview page is exported in the profile.
   *   Remove this todo when ISAICP-5176 is fixed.
   *
   * @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-5176
   *
   * @When I visit the community overview( page)
   */
  public function visitCommunityOverviewPage(): void {
    $this->visitPath('/collections');
  }

  /**
   * Creates a number of communities with data provided in a table.
   *
   * Table format:
   * @codingStandardsIgnoreStart
   * | title                   | abstract                                   | access url                             | closed | creation date    | description                                                                                                        | content creation                                  | featured | logo | moderation | modification date | owner | state                                              |
   * | Dog owner community    | Read up on all about <strong>dogs</strong> | http://dogtime.com/dog-breeds/profiles | yes|no | 28-01-1995 12:05 | The Afghan Hound is elegance personified.                                                                          | facilitators and authors|members|registered users | yes      |      | yes        |                   |       |                                                    |
   * | Cats community 4 ever! | Cats are cool!                             | http://mashable.com/category/cats/     | yes|no | 28-01-1995 12:06 | The domestic cat (Felis catus or Felis silvestris catus) is a small usually furry domesticated carnivorous mammal. | facilitators and authors|members|registered users | no       |      | no         |                   |       | draft|proposed|validated|archival request|archived |
   * @codingStandardsIgnoreEnd
   *
   * Only the title field is required.
   *
   * @param \Behat\Gherkin\Node\TableNode $community_table
   *   The community data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )communities:
   */
  public function givenCommunities(TableNode $community_table): void {
    $aliases = self::FIELD_ALIASES;

    foreach ($community_table->getColumnsHash() as $community) {
      $values = [];
      // Replace the column aliases with the actual field names.
      foreach ($community as $key => $value) {
        if (array_key_exists($key, $aliases)) {
          $values[$aliases[$key]] = $value;
        }
        else {
          throw new \Exception("Unknown column '$key' in community table.");
        }
      }

      // Convert user friendly values to machine values.
      $values = $this->convertValueAliases($values);

      // Provide default values.
      $values = $this->provideDefaultValues($values);

      $this->createCommunity($values);
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
        'facilitators and authors' => ContentCreationOptions::FACILITATORS_AND_AUTHORS,
        'members' => ContentCreationOptions::MEMBERS,
        'registered users' => ContentCreationOptions::REGISTERED_USERS,
      ],
      'field_ar_closed' => ['no' => 0, 'yes' => 1],
      'field_ar_state' => [
        'draft' => 'draft',
        'proposed' => 'proposed',
        'validated' => 'validated',
        'archival request' => 'archival_request',
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
   * Creates a community from the given property and field data.
   *
   * @param array $values
   *   An optional associative array of values, keyed by property name.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   A new community entity.
   *
   * @throws \Exception
   *   Thrown when a given image is not found.
   */
  protected function createCommunity(array $values): RdfInterface {
    // Add images.
    $image_fields = ['field_ar_banner', 'field_ar_logo'];
    foreach ($image_fields as $field_name) {
      if (!empty($values[$field_name])) {
        foreach ($values[$field_name] as &$filename) {
          $filename = [$this->createFile($filename)->id()];
        }
      }
    }

    // If the community is featured we need to create the meta entity that
    // stores this information after creating the community.
    $is_featured = in_array(strtolower((string) ($values['feature'] ?? '')), [
      'y',
      'yes',
    ]);

    /** @var \Drupal\collection\Entity\CommunityInterface $community */
    $community = $this->createRdfEntity('collection', $values);

    if ($is_featured) {
      $community->feature();
    }

    $this->communities[$community->id()] = $community;

    // We have to force reindex of affiliated solutions so the relationship
    // with this community will be indexed in Solr.
    if (!$community->get('field_ar_affiliates')->isEmpty()) {
      foreach ($community->get('field_ar_affiliates')->referencedEntities() as $solution) {
        // Reindex the solution, so that the value of the "community" computed
        // field will be populated and indexed correctly.
        $this->forceSearchApiReindex($solution);
      }
    }

    return $community;
  }

  /**
   * Creates a community with data provided in a table.
   *
   * @codingStandardsIgnoreStart
   * Table format:
   * | title            | Open Data Initiative                               |
   * | author           | Mightily Oats                                      |
   * | logo             | logo.png                                           |
   * | featured         | yes|no                                             |
   * | moderation       | yes|no                                             |
   * | closed           | yes|no                                             |
   * | content creation | facilitators and authors|members|registered users  |
   * | metadata url     | https://ec.europa.eu/my/url                        |
   * | state            | draft|proposed|validated|archival request|archived |
   * @codingStandardsIgnoreEnd
   *
   * Only the title field is required.
   *
   * @param \Behat\Gherkin\Node\TableNode $community_table
   *   The community data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )community:
   */
  public function givenCommunity(TableNode $community_table): void {
    $aliases = self::FIELD_ALIASES;

    $values = [];
    // Replace the column aliases with the actual field names.
    foreach ($community_table->getRowsHash() as $key => $value) {
      if (array_key_exists($key, $aliases)) {
        $values[$aliases[$key]] = $value;
      }
      else {
        throw new \Exception("Unknown column '$key' in community table.");
      }
    }

    // Convert user friendly values to machine values.
    $values = $this->convertValueAliases($values);

    // Provide default values.
    $values = $this->provideDefaultValues($values);

    $this->createCommunity($values);
  }

  /**
   * Deletes a community.
   *
   * @param string $community
   *   The title of the community.
   *
   * @When I delete the :community community
   */
  public function deleteCommunity(string $community): void {
    $community = $this->getCommunityByName($community);
    $community->skip_notification = TRUE;
    $community->delete();
  }

  /**
   * Checks the number of available communities.
   *
   * @param int $number
   *   The expected number of communities.
   *
   * @Then I should have :number community(s)
   */
  public function assertCommunityCount(int $number): void {
    $this->assertRdfEntityCount($number, 'collection');
  }

  /**
   * Subscribes the given users to the given communities.
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
   *   Thrown when a community is not found.
   *
   * @Given (the following )community user membership(s):
   */
  public function givenCommunityUserMemberships(TableNode $membership_table): void {
    foreach ($membership_table->getColumnsHash() as $values) {
      $group = $this->getCommunityByName($values['collection']);
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
   * @Given (the following )community menu structure:
   */
  public function givenCommunityMenuStructure(TableNode $menu_table): void {
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
   * Asserts that a user is an owner of the given community.
   *
   * To be owner of a community, a user should be an administrator,
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
   * @Given (the user ):username should be the owner of the :rdf_entity community
   */
  public function assertCommunityOwnership(string $username, string $rdf_entity): void {
    $user = user_load_by_name($username);
    if (empty($user)) {
      throw new \Exception("User {$username} could not be found.");
    }

    $community = $this->getRdfEntityByLabel($rdf_entity, 'collection');
    $owner_roles = [
      OgRoleInterface::ADMINISTRATOR,
      OgRoleInterface::AUTHENTICATED,
      'facilitator',
    ];

    $this->assertOgGroupOwnership($user, $community, $owner_roles);
  }

  /**
   * Asserts that the current user is an owner of the given community.
   *
   * @param string $rdf_entity
   *   The label of the group entity.
   *
   * @throws \Exception
   *    Throws an exception when the user is not found.
   *
   * @see assertCommunityOwnership()
   *
   * @Given I should own the :rdf_entity community
   */
  public function assertCommunityOwnershipCurrentUser(string $rdf_entity): void {
    if (!$current_user = $this->userManager->getCurrentUser()) {
      throw new \Exception("No current user.");
    }
    $this->assertCommunityOwnership($current_user->name, $rdf_entity);
  }

  /**
   * Removes any created communities.
   *
   * @AfterScenario @api
   */
  public function cleanCommunities(): void {
    if (empty($this->communities)) {
      return;
    }

    // Since we might be cleaning up many communities, temporarily disable the
    // feature to commit the index after every query.
    $this->disableCommitOnUpdate();

    // Remove any communities that were created.
    foreach ($this->communities as $community) {
      $community->skip_notification = TRUE;
      $community->delete();
    }
    $this->communities = [];
    $this->enableCommitOnUpdate();
  }

  /**
   * Checks that a user has the available state options for the community.
   *
   * The method also checks that these options are the only options available.
   * This method will log in as each user in sequence, so take care to only use
   * it when the currently logged in user can be discarded.
   *
   * Table format:
   * | collection   | user | buttons         |
   * | Community A | John | Save as draft   |
   * | Community B | Jack | Update, Publish |
   *
   * @param \Behat\Gherkin\Node\TableNode $check_table
   *   The table with the triplets community-user-buttons.
   *
   * @throws \Exception
   *    Thrown when the user does not exist.
   *
   * @todo Maybe there is a better definition available here like 'The
   * following state buttons should be available for the user on the
   * community'.
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
      $this->visitEntityForm('edit', $values['collection'], 'collection');
      $buttons = $this->explodeCommaSeparatedStepArgument($values['buttons']);
      $this->assertSubmitButtonsVisible($buttons);
    }
  }

  /**
   * Checks that a user has access to the delete button on the community form.
   *
   * Table format:
   * | collection   | user | delete link |
   * | Community A | John | yes         |
   * | Community B | Jack | no          |
   *
   * @param \Behat\Gherkin\Node\TableNode $check_table
   *   The table with the triplets community-user-link visibility.
   *
   * @throws \Exception
   *    Thrown when the user does not exist.
   *
   * @Then the visibility of the delete link should be as follows for these users in these communities:
   */
  public function verifyDeleteLinkVisibility(TableNode $check_table): void {
    foreach ($check_table->getColumnsHash() as $values) {
      $user = $this->getUserByName($values['user']);
      $community = $this->getCommunityByName($values['collection']);
      $visible = $values['delete link'] === 'yes';
      $this->assertGroupEntityOperation($visible, 'delete', $community, $user);
    }
  }

  /**
   * Navigates to the community leave confirmation form.
   *
   * @param string $label
   *   The label of the community group.
   *
   * @Given I am about to leave( the) :label( community)
   */
  public function visitCommunityLeaveConfirmationPage(string $label): void {
    $community = $this->getEntityByLabel('rdf_entity', $label, 'collection');
    $encoded_id = UriEncoder::encodeUrl($community->id());
    $this->visitPath("/rdf_entity/$encoded_id/leave");
  }

  /**
   * Asserts that a given or current user is member of community list.
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
   * @Then I am member of :labels community(s)
   * @Then user :user_name is member of :labels community(s)
   */
  public function assertUserIsMemberOfCommunity(string $labels, ?string $user_name = NULL): void {
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
      /** @var \Drupal\collection\Entity\CommunityInterface $community */
      $community = $this->getEntityByLabel('rdf_entity', $label, 'collection');
      if (!$community->getMembership((int) $account->id(), [])) {
        $failures[] = $label;
      }
    }

    if ($failures) {
      throw new \Exception("User $user_name is not member of the next communities: '" . implode("', '", $failures) . "'");
    }
  }

  /**
   * Updates the name of a community.
   *
   * @param string $community
   *   The name of the community to update.
   * @param string $name
   *   The new anem for the community.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when the updated community cannot be saved.
   *
   * @When I change the name of the :community community to :name
   */
  public function updateCommunityName(string $community, string $name): void {
    $entity = $this->getCommunityByName($community);
    $entity->setName($name)->save();
  }

  /**
   * Creates the standard 'Joinup' community.
   *
   * @BeforeScenario @joinup_community&&@api
   *
   * @see joinup_community.module
   */
  public function createJoinupCommunity(): void {
    $this->createCommunity([
      'id' => JoinupCommunityHelper::getCommunityId(),
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
