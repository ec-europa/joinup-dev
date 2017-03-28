<?php

/**
 * @file
 * Contains \UserSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Core\Entity\EntityInterface;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use Drupal\joinup\Traits\BrowserCapabilityDetectionTrait;
use Drupal\joinup\Traits\FileTrait;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\OgTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\TraversingTrait;
use Drupal\joinup\Traits\UserTrait;
use Drupal\joinup\Traits\UtilityTrait;
use Drupal\joinup\Traits\WorkflowTrait;
use Drupal\joinup\Traits\WysiwygTrait;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\search_api\Entity\Server;
use Drupal\User\Entity\User;

/**
 * Behat step definitions that are generally useful for the Joinup project.
 */
class JoinupSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use BrowserCapabilityDetectionTrait;
  use FileTrait;
  use NodeTrait;
  use OgTrait;
  use RdfEntityTrait;
  use TraversingTrait;
  use UserTrait;
  use UtilityTrait;
  use WorkflowTrait;
  use WysiwygTrait;

  /**
   * Test users.
   *
   * @var \Drupal\user\Entity\User[]
   */
  protected $registeredUsers = [];

  /**
   * Creates a user with data provided in a table.
   *
   * Table format:
   * | Username   | Mr Bond            |
   * | Password   | Bond007            |
   * | E-mail     | james.bond@mi5.org |
   *
   * @param \Behat\Gherkin\Node\TableNode $user_table
   *   The user data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )user:
   */
  public function givenUser(TableNode $user_table) {
    // Replace the column aliases with the actual field names.
    $values = $this->translateUserFieldAliases($user_table->getRowsHash());
    $this->handleFileFields($values, 'user', 'user');

    // Login method needs a password.
    if (!isset($values['pass'])) {
      $values['pass'] = $values['name'];
    }
    if (!isset($values['mail'])) {
      $values['mail'] = $values['name'] . '@example.com';
    }

    $user = (object) $values;
    $this->userCreate($user);
  }

  /**
   * Creates and authenticates a user with the given og role(s).
   *
   * Multiple roles can be passed separated with comma.
   *
   * @param string $roles
   *   A comma separated list of roles to assign to the user.
   * @param string $rdf_entity
   *   The label of the collection or solution of which the user is a member.
   * @param string $rdf_entity_bundle
   *   The RDF entity bundle, either 'collection' or 'solution'.
   *
   * @throws \Exception
   *   Thrown when a collection or solution with the given label doesn't exist.
   *
   * @Given I am logged in as a user with the :role role(s) of the :rdf_entity :rdf_entity_bundle
   * @Given I am logged in as a/an :role of the :rdf_entity :rdf_entity_bundle
   *
   * @see \Drupal\DrupalExtension\Context\DrupalContext::assertAuthenticatedByRole()
   */
  public function assertAuthenticatedByOgRole($roles, $rdf_entity, $rdf_entity_bundle) {
    $entity = $this->getRdfEntityByLabel($rdf_entity, $rdf_entity_bundle);
    if (!$entity) {
      throw new \Exception("No entity found with label $rdf_entity");
    }
    $roles = $this->explodeCommaSeparatedStepArgument($roles);
    $roles = $this->getOgRoles($roles, $entity);

    // Check if a user with this role is already logged in.
    if (!$this->loggedInWithOgRoles($roles, $entity)) {
      $random = $this->getRandom()->name(8);
      // Create user (and project)
      $user = (object) [
        'name' => $random,
        'pass' => $random,
      ];
      $user->mail = "{$user->name}@example.com";
      $this->userCreate($user);

      // Load the actual user account.
      $account = User::load($user->uid);
      $this->subscribeUserToGroup($account, $entity, $roles);

      // Login.
      $this->login($user);
    }
  }

  /**
   * Changes the role of a user within a collection or solution.
   *
   * Use this to e.g. test promotion or demotion of facilitators.
   *
   * @param string $rdf_entity
   *   The label of the collection or solution of which the user is a member.
   * @param string $rdf_entity_bundle
   *   The RDF entity bundle, either 'collection' or 'solution'.
   * @param string $roles
   *   A comma separated list of roles to assign to the user. All previously
   *   assigned roles will be discarded.
   *
   * @throws \Exception
   *   Thrown when a collection or solution with the given label doesn't exist.
   *
   * @Given my role(s) in the :rdf_entity :rdf_entity_bundle change(s) to :roles
   */
  public function updateGroupRoles($rdf_entity, $rdf_entity_bundle, $roles) {
    $entity = $this->getRdfEntityByLabel($rdf_entity, $rdf_entity_bundle);
    if (!$entity) {
      throw new \Exception("No entity found with label $rdf_entity");
    }
    $roles = $this->explodeCommaSeparatedStepArgument($roles);
    $roles = $this->getOgRoles($roles, $entity);

    $current_user = $this->getUserManager()->getCurrentUser();
    $account = User::load($current_user->uid);

    $this->subscribeUserToGroup($account, $entity, $roles);
  }

  /**
   * Checks that the logged in user has the given OG roles in the given group.
   *
   * If the user has more than the required roles, he might have permissions
   * from the rest of the roles that will lead the test to a false positive.
   * For this reason, we request check for the specific roles passed.
   *
   * @param \Drupal\og\Entity\OgRole[] $roles
   *   An array of roles to check.
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group that is checked if the user has the role.
   *
   * @return bool
   *   Returns TRUE if the current logged in user has this role (or roles).
   *
   * @see \Drupal\DrupalExtension\Context\RawDrupalContext::loggedInWithRole
   */
  protected function loggedInWithOgRoles(array $roles, EntityInterface $group) {
    if ($this->getUserManager()->currentUserIsAnonymous() || !$this->loggedIn()) {
      return FALSE;
    }
    $current_user = $this->getUserManager()->getCurrentUser();
    $user = \Drupal::entityTypeManager()->getStorage('user')->loadUnchanged($current_user->uid);
    $membership = Og::getMembership($group, $user);
    if (empty($membership)) {
      return FALSE;
    }
    if ($roles == $membership->getRolesIds()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Register a user.
   *
   * @Given the following user registration at :location:
   */
  public function submitRegistrationForm(TableNode $table, $location) {
    $this->visitPath($location);

    // Fill in fields.
    foreach ($table->getRowsHash() as $field => $value) {
      $this->getSession()->getPage()->fillField($field, $value);
      if ($field == 'Username') {
        $username = $value;
      }
    };
    // Submit form.
    $this->getSession()->getPage()->pressButton("Create new account");

    // Get the last registered user.
    $results = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $username]);
    /** @var \Drupal\user\UserInterface $user */
    $user = reset($results);

    if ($user) {
      // Track user for auto delete in tear-down.
      $this->registeredUsers[$user->id()] = $user;
    }
    else {
      throw new Exception('User not registered.');
    }
  }

  /**
   * Remove any created registered users.
   *
   * @AfterScenario
   */
  public function cleanRegisteredUsers() {
    // Remove any users that were created.
    if (!empty($this->registeredUsers)) {
      /** @var \Drupal\user\Entity\User $user */
      foreach ($this->registeredUsers as $user) {
        $user->delete();
      }
    }
  }

  /**
   * Reset the request graphs of rdf entity before every step.
   *
   * The request graphs are tied to an individual request.
   *
   * @BeforeStep
   */
  public function resetRequestGraphs($event) {
    /** @var RdfEntitySparqlStorage $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
    $storage->getGraphHandler()->resetRequestGraphs();
  }

  /**
   * Checks that the given select field has the given options.
   *
   * @param string $select
   *   The name of the field element.
   * @param string $options
   *   The options to be checked separated by comma. Only labels are accepted.
   *
   * @Then (the ):select field should contain the :options option(s)
   */
  public function checkSelectContainsOptions($select, $options) {
    $field = $this->findSelect($select);
    $available_options = array_values($this->getSelectOptions($field));
    $options = $this->explodeCommaSeparatedStepArgument($options);

    \PHPUnit_Framework_Assert::assertArraySubset($options, $available_options, "The '{$select}' select doesn't contain all the wanted options.");
  }

  /**
   * Checks if the given select field does not contain any of the given values.
   *
   * @param string $select
   *   The name of the field element.
   * @param string $options
   *   The options to be checked separated by comma.
   *
   * @throws Exception
   *    Throws an exception when a value exists.
   *
   * @Then (the ):select field should not contain the :options option(s)
   */
  public function checkSelectDoesNotContainOptions($select, $options) {
    $field = $this->findSelect($select);
    $available_options = array_values($this->getSelectOptions($field));
    $options = $this->explodeCommaSeparatedStepArgument($options);

    $intersection = array_intersect($available_options, $options);

    if (!empty($intersection)) {
      throw new \Exception("The select '{$select}' should not contain the options: " . implode(', ', $intersection));
    }
  }

  /**
   * Checks that a select field has exclusively the provided options.
   *
   * @param string $select
   *   The name of the select element.
   * @param string $options
   *   A comma-separated list of options to be present.
   *
   * @Then the :select select available options should be :options
   */
  public function assertSelectOptions($select, $options) {
    $field = $this->findSelect($select);
    $available_options = $this->getSelectOptions($field);
    sort($available_options);

    $options = $this->explodeCommaSeparatedStepArgument($options);
    sort($options);

    // The PHPUnit assertion will allow to understand easily which values are
    // missing and which one are extra.
    \PHPUnit_Framework_Assert::assertEquals($options, $available_options, "The '{$select}' select options don't match the wanted ones.");
  }

  /**
   * Commit the solr index for testing purposes.
   *
   * @Then I commit the solr index
   */
  public function iCommitTheSolrIndex() {
    $search_servers = Server::loadMultiple();
    /** @var \Drupal\search_api\Entity\Server $search_server */
    foreach ($search_servers as $search_server) {
      $backend_id = $search_server->getBackendId();
      if (!$backend_id == 'search_api_solr') {
        continue;
      }
      /** @var \Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend $backend */
      $backend = $search_server->getBackend();
      /** @var \Drupal\search_api_solr\SolrConnectorInterface $connector */
      $connector = $backend->getSolrConnector();
      $update = $connector->getUpdateQuery();
      $update->addCommit(TRUE, TRUE);
      $connector->update($update);
    }
  }

  /**
   * Navigates to the edit page display of a node entity.
   *
   * @param string $bundle
   *   The type of the node entity.
   * @param string $title
   *   The title of the node.
   *
   * @When (I )go to the :bundle (content ) :title edit screen
   * @When (I )visit the :bundle (content ) :title edit screen
   */
  public function visitNodeEditForm($bundle, $title) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getNodeByTitle($title, $bundle);
    $this->visitPath($node->url('edit-form'));
  }

  /**
   * Navigates to the canonical page display of a node page.
   *
   * This step is only to be used in scenario outlines.
   * If possible, use a specific page selector.
   *
   * @param string $type
   *   The type of the node entity.
   * @param string $title
   *   The name of the news page.
   *
   * @When (I )go to the content page of the type :type with the title :title
   * @When (I )visit the content page of the type :type with the title :title
   */
  public function visitNodePage($type, $title) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, $type);
    $this->visitPath($node->url());
  }

  /**
   * Checks if a content entity is published.
   *
   * @param string $bundle
   *   The node bundle.
   * @param string $title
   *   The node title.
   *
   * @throws \Exception
   *   Throws an exception if the content is not published.
   *
   * @Then the :title :bundle (content )should be published
   */
  public function assertNodePublished($bundle, $title) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getNodeByTitle($title, $bundle);
    if (!$node->isPublished()) {
      throw new \Exception("$title content should be published but it is not.");
    }
  }

  /**
   * Checks if a content entity is published.
   *
   * @param string $bundle
   *   The node bundle.
   * @param string $title
   *   The node title.
   *
   * @throws \Exception
   *   Throws an exception if the content is not published.
   *
   * @Then the :title :bundle (content )should not be published
   */
  public function assertNodeNotPublished($bundle, $title) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getNodeByTitle($title, $bundle);
    if ($node->isPublished()) {
      throw new \Exception("$title content should not be published but it is.");
    }
  }

  /**
   * Checks if the given content entity has the correct number of revisions.
   *
   * @param string $bundle
   *   The node bundle.
   * @param string $title
   *   The node title.
   * @param int $count
   *   The expected number of revisions.
   *
   * @Then the :title :bundle (content )should have :count revision(s)
   */
  public function assertNodeRevisionCount($bundle, $title, $count) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getNodeByTitle($title, $bundle);

    $revision_count = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
      ->allRevisions()
      ->condition('nid', $node->id())
      ->count()
      ->execute();

    PHPUnit_Framework_Assert::assertEquals($count, $revision_count);
  }

  /**
   * Replaces human readable labels and values with their real counterparts.
   *
   * This allows us to:
   * - use human readable labels like 'short title' in test scenarios, and map
   *   them to the actual field names like 'field_short_title'.
   * - use human readable values such as a collection name instead of the URL
   *   that is actually used in the OG reference field.
   *
   * @param \Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope $scope
   *   An object containing the entity properties and fields that are to be used
   *   for creating the node as properties on the object.
   *
   * @BeforeNodeCreate
   */
  public static function massageFieldsBeforeNodeCreate(BeforeNodeCreateScope $scope) {
    $node = $scope->getEntity();

    // Replace field label aliases with the actual field names.
    foreach (get_object_vars($node) as $alias => $value) {
      $name = self::getNodeFieldNameFromAlias($node->type, $alias);
      if ($name !== $alias) {
        unset($node->alias);
        $node->$name = $value;
      }
    }

    // Replace collection and solution group references that use titles with the
    // actual URI. Note that this fails if a URI is supplied for a collection or
    // solution in a Behat test. This is by design, the URIs are not exposed to
    // end users of the site so they should not be used in BDD scenarios.
    if (!empty($node->{OgGroupAudienceHelperInterface::DEFAULT_FIELD})) {
      $entity = self::getRdfEntityByLabel($node->{OgGroupAudienceHelperInterface::DEFAULT_FIELD});
      $node->{OgGroupAudienceHelperInterface::DEFAULT_FIELD} = $entity->id();
    }

    // Replace the human readable publication statuses with the boolean values
    // that are expected by the Node module.
    if (!empty($node->status)) {
      $mapping = [
        'unpublished' => 0,
        'published' => 1,
      ];
      // Force the use of human readable values in Behat test scenarios, throw
      // an exception if the numeric values are used.
      if (!array_key_exists($node->status, $mapping)) {
        $supported_statii = implode(', ', array_keys($mapping));
        throw new \UnexpectedValueException("Unexpected status '{$node->status}'. Supported statuses are: $supported_statii.");
      }
      // Replace the human readable value with the expected boolean.
      $node->status = $mapping[$node->status];
    }
  }

  /**
   * Returns the actual field name for a given node field alias.
   *
   * @param string $type
   *   The node type for which to return the field name.
   * @param string $alias
   *   The human readable node field alias.
   *
   * @return string
   *   The actual field name, or the original string if the alias is not known.
   */
  protected static function getNodeFieldNameFromAlias($type, $alias) {
    $aliases = [
      'discussion' => [
        'state' => 'field_state',
        'content' => 'body',
      ],
      'document' => [
        'short title' => 'field_short_title',
        'type' => 'field_type',
        'licence' => 'field_licence',
      ],
      'event' => [
        'short title' => 'field_short_title',
        'start date' => 'field_start_date',
        'state' => 'field_state',
      ],
      'news' => [
        'headline' => 'field_news_headline',
        'state' => 'field_state',
      ],
      'shared' => [
        'collection' => OgGroupAudienceHelperInterface::DEFAULT_FIELD,
        'solution' => OgGroupAudienceHelperInterface::DEFAULT_FIELD,
      ],
    ];

    // Check both the node type specific aliases as well as the shared aliases.
    foreach ([$type, 'shared'] as $key) {
      if (!empty($aliases[$key][$alias])) {
        return $aliases[$key][$alias];
      }
    }

    return $alias;
  }

  /**
   * Presses button with specified id|name|title|alt|value at a widget.
   *
   * Example: When I press "Log In" at "Fieldset"
   * Example: And I press "Log In" at "Custom widget".
   *
   * @When I press :button at( the) :field( field)
   */
  public function pressButtonInWidget($button, $field) {
    // Fixes a step argument (with \\" replaced back to ")
    // @see: Behat\MinkExtension\Context\MinkContext::fixStepArgument
    $button = str_replace('\\"', '"', $button);

    // First check if a fieldset exists containing the given field name. This
    // is used for inline entity forms.
    $element = $this->getSession()->getPage()->find('named', [
      'fieldset',
      $field,
    ]);

    // If this doesn't exist, search for a multivalue entity reference field
    // containing a label for the given field name.
    if (empty($element)) {
      $xpath = '//table[contains(concat(" ", normalize-space(@class), " "), " field-multiple-table ") and //label[text()="' . $field . '"]]/ancestor::div[contains(concat(" ", normalize-space(@class), " "), " form-wrapper ")]';
      $element = $this->getSession()->getPage()->find('xpath', $xpath);
    }

    $element->pressButton($button);
  }

  /**
   * Fills a date or time field at a datetime widget.
   *
   * Example: When I fill in "Start date" with the date "29-08-2016".
   * Example: When I fill in "Start date" with the time "26:59:00".
   *
   * @param string $field_group
   *   The field component's label.
   * @param string $field_name
   *   The field to be filled.
   * @param string $value
   *   The value of the field.
   *
   * @throws \Exception
   *    Thrown when more than one elements match the given field in the given
   *    field group.
   *
   * @When I fill in :field_group with the :field_name :value
   */
  public function fillDateField($field_group, $field_name, $value) {
    // Get the datetime fields and check which one of them has the label
    // provided. This is because datetime fields have use a complex widget and
    // render their title as a simple header, not as a label for the field.
    $field_selectors = $this->getSession()->getPage()->findAll('css', '.field--type-datetime');
    $field_selectors = array_filter($field_selectors, function ($field_selector) use ($field_group) {
      return $field_selector->has('named', ['content', $field_group]);
    });
    if (count($field_selectors) > 1) {
      throw new \Exception("More than one elements were found.");
    }
    $field_selector = reset($field_selectors);

    $field_selector->fillField(ucfirst($field_name), $value);
  }

  /**
   * Checks that the given node has the expected workflow state.
   *
   * @param string $title
   *   The title of the node to check.
   * @param string $type
   *   The node type.
   * @param string $state
   *   The expected state.
   *
   * @Then the :title :type content should have the :state state
   */
  public function assertNodeWorkflowState($title, $type, $state) {
    $node = $this->getNodeByTitle($title, $type);
    $actual = $this->getEntityStateField($node)->get('value')->getString();
    \PHPUnit_Framework_Assert::assertEquals($state, $actual, "The $title $type content has the expected state '$state' (actual: '$actual')");
  }

  /**
   * Checks if the given content belongs to the given parent rdf entity.
   *
   * If there are multiple entities or parents with the same title, then
   * only the first one is checked.
   *
   * @param string $parent
   *   The name of the parent.
   * @param string $parent_bundle
   *   The bundle of the parent.
   * @param string $content_bundle
   *   The bundle of the content.
   * @param string $content
   *   The title of the content.
   *
   * @Then the :parent :parent_bundle has a :content_bundle titled :content
   */
  public function assertContentOgMembership($parent, $parent_bundle, $content_bundle, $content) {
    $this->assertOgMembership($parent, $parent_bundle, $content, $content_bundle);
  }

  /**
   * Asserts the presence of a tile element with a certain heading.
   *
   * @param string $heading
   *   The heading of the tile.
   *
   * @throws \Exception
   *   Thrown when the expected tile is not found.
   *
   * @Then I (should )see the :heading tile
   */
  public function assertTilePresent($heading) {
    $results = $this->getSession()->getPage()->findAll('css', '.listing__item--tile .listing__title');
    foreach ($results as $result) {
      /** @var \Behat\Mink\Element\Element $result */
      if ($result->getText() === $heading) {
        return;
      }
    }

    throw new \Exception("The tile '$heading' was not found on the page");
  }

  /**
   * Asserts that there are no tile elements with a certain heading.
   *
   * @param string $heading
   *   The heading to search.
   *
   * @throws \Exception
   *   Thrown when a tile with the unwanted heading is found.
   *
   * @Then I (should )not see the :heading tile
   */
  public function assertTileNotPresent($heading) {
    $results = $this->getSession()->getPage()->findAll('css', '.listing__item--tile .listing__title');
    foreach ($results as $result) {
      /** @var \Behat\Mink\Element\Element $result */
      if ($result->getText() === $heading) {
        throw new \Exception("The tile '$heading' was found on the page");
      }
    }
  }

  /**
   * Checks that the given tiles are found in the given block.
   *
   * The weight of the tiles are also taken into account.
   *
   * Table format:
   * | title        |
   * | Tile title 1 |
   * | Tile title 2 |
   *
   * @Then I( should) see the( following) tiles in the :region region:
   */
  public function assertTilesInBlock(TableNode $titles_table, $region) {
    $results = $this->getTiles($region);
    $headings_in_page = array_map(function ($result) {
      return $result->getText();
    }, $results);
    $headings_expected = $titles_table->getColumn(0);
    \PHPUnit_Framework_Assert::assertEquals($headings_expected, $headings_in_page, "The expected tiles are not in the page or not in the proper order.");
  }

  /**
   * Assert that the given form submission buttons are present on the page.
   *
   * @param string $buttons
   *   A comma separated list of button labels.
   * @param int $count
   *   Optional number of buttons that are expected to be present. Use this to
   *   verify that no unexpected additional buttons are present on the page. If
   *   omitted the number of buttons will not be verified.
   *
   * @throws \Exception
   *   Thrown when an expected button is not present or when the number of
   *   buttons is wrong.
   *
   * @Then (the following )button(s) should be present :buttons
   * @Then the following :count button(s) should be present :buttons
   */
  public function assertFormSubmitButtonsVisible($buttons, $count = NULL) {
    $buttons = $this->explodeCommaSeparatedStepArgument($buttons);

    $page = $this->getSession()->getPage();
    $not_found = [];
    foreach ($buttons as $button) {
      if (!$page->findButton($button)) {
        $not_found[] = $button;
      }
    }

    if (!empty($not_found)) {
      throw new \Exception('Button(s) expected, but not found: ' . implode(', ', $not_found));
    }

    if (!empty($count)) {
      // Only check the actual form submit buttons, ignore other buttons that
      // might be present in wysiwygs or are used to add multiple values to a
      // field.
      $actual = count($this->getSession()->getPage()->findAll('xpath', '//div[contains(concat(" ", normalize-space(@class), " "), " form-actions ")]//input[@type = "submit"]'));
      \PHPUnit_Framework_Assert::assertEquals($count, $actual);
    }
  }

  /**
   * Assert that certain buttons are not present on the page.
   *
   * @param string $buttons
   *   A comma separated list of button labels.
   *
   * @throws \Exception
   *   Thrown when an unexpected button is present.
   *
   * @Then (the following )buttons should not be present :buttons
   */
  public function assertButtonsNotVisible($buttons) {
    $buttons = $this->explodeCommaSeparatedStepArgument($buttons);

    $page = $this->getSession()->getPage();
    $found = [];
    foreach ($buttons as $button) {
      if ($page->findButton($button)) {
        $found[] = $button;
      }
    }

    if (!empty($found)) {
      throw new \Exception('Button(s) should not be present, but were found: ' . implode(', ', $found));
    }
  }

  /**
   * Click the given link in the plus button menu.
   *
   * On JavaScript enabled browsers this will first click the plus button to
   * open the menu.
   *
   * @param string $link
   *   The link text of the link to click.
   *
   * @When I click :link in the plus button menu
   */
  public function clickLinkInPlusButtonMenu($link) {
    // Check if we are running in an environment that supports JavaScript like
    // Selenium or PhantomJS. If this is the case the plus button menu will be
    // closed by default and needs to be opened first.
    $this->openPlusButtonMenu();
    $this->getSession()->getPage()->clickLink($link);
  }

  /**
   * Opens the plus button menu on JS-enabled browsers.
   *
   * @When I open the plus button menu
   */
  public function openPlusButtonMenu() {
    if ($this->browserSupportsJavascript()) {
      $driver = $this->getSession()->getDriver();
      $this->getSession()->getDriver()->click('//div[contains(concat(" ", normalize-space(@class), " "), " add-content-menu ")]//button');

      // Wait for the menu opening animation to end before continuing.
      $end = microtime(TRUE) + 5;
      do {
        usleep(100000);
        // The plus button opening animation runs from the top right to the
        // bottom left. Wait for the last element to become visible to ensure
        // the menu is fully opened.
      } while (microtime(TRUE) < $end && !$driver->isVisible('//div[contains(concat(" ", normalize-space(@class), " "), " add-content-menu ")]//li[last()]'));
    }
  }

  /**
   * Enters the given text in the given WYSIWYG editor.
   *
   * If this is running on a JavaScript enabled browser it will first click the
   * 'Source' button so the text can be entered as normal HTML.
   *
   * @param string $text
   *   The text to enter in the WYSIWYG editor.
   * @param string $label
   *   The label of the field containing the WYSIWYG editor.
   *
   * @When I enter :text in the :label wysiwyg editor
   */
  public function enterTextInWysiwyg($text, $label) {
    // If we are running in a JavaScript enabled browser, first click the
    // 'Source' button so we can enter the text as HTML and get the same result
    // as in a non-JS browser.
    if ($this->browserSupportsJavascript()) {
      $this->pressWysiwygButton($label, 'Source');
      $this->setWysiwygText($label, $text);
    }
    else {
      $this->getSession()->getPage()->fillField($label, $text);
    }
  }

  /**
   * Create comment entities.
   *
   * Table format:
   * @codingStandardsIgnoreStart
   * | subject        | message         | author   | mail          | name      | parent      |
   * | Subject 1      | Comment body    |          | anon@test.com | Anonymous | Parent node |
   * | Subject 2      | Comment body    | John Doe |               |           | Parent node |
   * @codingStandardsIgnoreEnd
   *
   * If author is filled, mail and name are not required.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The comment data.
   *
   * @throws \Exception
   *   Thrown when the parent node cannot be found, or when the author cannot
   *   be found.
   *
   * @Given comments:
   */
  public function createComments(TableNode $table) {
    foreach ($table->getColumnsHash() as $comment_values) {
      $query = \Drupal::entityQuery('node')
        ->condition('title', $comment_values['parent'])
        ->range(0, 1);
      $result = $query->execute();

      if (empty($result)) {
        throw new \InvalidArgumentException('Unable to load parent of comment.');
      }

      // Reload from database to avoid caching issues and get latest version.
      $id = reset($result);

      $query = \Drupal::entityQuery('user')
        ->condition('name', $comment_values['author'])
        ->range(0, 1);
      $result = $query->execute();
      $uid = reset($result);

      if (empty($result)) {
        throw new \InvalidArgumentException('Unable to load author of comment.');
      }

      // Replace field_body alias if present.
      if (isset($comment_values['message'])) {
        $comment_values['field_body'] = $comment_values['message'];
        unset($comment_values['message']);
      }

      unset($comment_values['parent']);
      unset($comment_values['author']);
      $comment_values['uid'] = $uid;
      $values = [
        'comment_type' => 'comment',
        'status' => CommentInterface::PUBLISHED,
        'uid' => $uid,
        'field_name' => 'field_comments',
        'entity_type' => 'node',
        'entity_id' => $id,
      ];
      $comment_values += $values;
      $comment = Comment::create($comment_values);
      $comment->save();
    }
  }

  /**
   * Checks the current workflow state on an entity edit form.
   *
   * @param string $state
   *   The expected workflow state.
   *
   * @throws \Exception
   *   Thrown when the current workflow state field is not shown on the page.
   *
   * @Then the current workflow state should be :state
   */
  public function assertCurrentWorkflowState($state) {
    $element = $this->getSession()->getPage()->find('css', 'div.current-workflow-state');
    if (empty($element)) {
      throw new \Exception('The current workflow state field is not present on the page.');
    }
    \PHPUnit_Framework_Assert::assertEquals($state, trim($element->getText()));
  }

  /**
   * Emulates closing and re-opening of the browser.
   *
   * @When I close and reopen the browser
   */
  public function restartSession() {
    /** @var \Symfony\Component\BrowserKit\CookieJar $cookie_jar */
    $cookie_jar = $this->getSession()->getDriver()->getClient()->getCookieJar();

    // We are going to keep all the cookies with a valid expire time. The
    // \Symfony\Component\BrowserKit\CookieJar::all() method already removes
    // expired cookies, so we need to strip the ones with a null expire time.
    // A null expire time means that the cookie is going to be deleted when the
    // browser is closed.
    $persistent = [];
    foreach ($cookie_jar->all() as $cookie) {
      if (!is_null($cookie->getExpiresTime())) {
        $persistent[] = $cookie;
      }
    }

    $this->getSession()->restart();
    $cookie_jar = $this->getSession()->getDriver()->getClient()->getCookieJar();

    foreach ($persistent as $cookie) {
      $cookie_jar->set($cookie);
    }
  }

  /**
   * Checks that the description of the field with given label is as expected.
   *
   * @param string $label
   *   The label of the field to check.
   * @param string $description
   *   The expected description.
   *
   * @throws \Exception
   *   Thrown when the field does not have a description.
   * @throws \PHPUnit_Framework_ExpectationFailedException
   *   Thrown when none of the field's descriptions match the given text.
   *
   * @Then I should see the description :description for (the ):label( field)
   */
  public function assertFieldDescription($label, $description) {
    $xpath = '//label[text()="' . $label . '"]/ancestor::div[contains(concat(" ", normalize-space(@class), " "), " form-item ")]//div[contains(concat(" ", normalize-space(@class), " "), " description ")]';
    $elements = $this->getSession()->getPage()->findAll('xpath', $xpath);
    if (empty($elements)) {
      throw new \Exception("The $label field does not have a description.");
    }
    foreach ($elements as $element) {
      try {
        \PHPUnit_Framework_Assert::assertEquals($description, trim($element->getText()));
        // The description was found, stop searching.
        return;
      }
      catch (\PHPUnit_Framework_ExpectationFailedException $e) {
        // The description did not match the expected string, keep searching.
      }
    }
    throw new \PHPUnit_Framework_ExpectationFailedException("The description $description was not found for the $label field.");
  }

  /**
   * Checks that the description of the field with given label is not present.
   *
   * @param string $label
   *   The label of the field to check.
   * @param string $description
   *   The description that is expected to be absent.
   *
   * @throws \Exception
   *   Thrown when the field does not have a description.
   *
   * @Then I should not see the description :description for (the ):label( field)
   */
  public function assertNoFieldDescription($label, $description) {
    $xpath = '//label[text()="' . $label . '"]/ancestor::div[contains(concat(" ", normalize-space(@class), " "), " form-item ")]//div[contains(concat(" ", normalize-space(@class), " "), " description ")]';
    $elements = $this->getSession()->getPage()->findAll('xpath', $xpath);
    foreach ($elements as $element) {
      \PHPUnit_Framework_Assert::assertNotEquals($description, trim($element->getText()));
    }
  }

}
