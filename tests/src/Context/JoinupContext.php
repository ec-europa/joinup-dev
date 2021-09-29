<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use Drupal\User\Entity\User;
use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\joinup\Traits\BrowserCapabilityDetectionTrait;
use Drupal\joinup\Traits\ContextualLinksTrait;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\FileTrait;
use Drupal\joinup\Traits\FormTrait;
use Drupal\joinup\Traits\KeyboardInteractionTrait;
use Drupal\joinup\Traits\MaterialDesignTrait;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\OgTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\TabledragTrait;
use Drupal\joinup\Traits\TraversingTrait;
use Drupal\joinup\Traits\UserTrait;
use Drupal\joinup\Traits\UtilityTrait;
use Drupal\joinup\Traits\WorkflowTrait;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_group\Entity\GroupContentInterface;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\Entity\PinnableGroupContentInterface;
use Drupal\meta_entity\Entity\MetaEntity;
use Drupal\node\Entity\Node;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og\OgRoleInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\search_api\Entity\Server;
use GuzzleHttp\Client;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use WebDriver\Exception\NoSuchElement;
use WebDriver\Exception\StaleElementReference;

/**
 * Behat step definitions that are generally useful for the Joinup project.
 */
class JoinupContext extends RawDrupalContext {

  use BrowserCapabilityDetectionTrait;
  use ContextualLinksTrait;
  use EntityTrait;
  use FileTrait;
  use FormTrait;
  use KeyboardInteractionTrait;
  use MaterialDesignTrait;
  use NodeTrait;
  use OgTrait;
  use RdfEntityTrait;
  use StringTranslationTrait;
  use TabledragTrait;
  use TraversingTrait;
  use UserTrait;
  use UtilityTrait;
  use WorkflowTrait;

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
    $this->createUser($user_table->getRowsHash());
  }

  /**
   * Asserts that the given html exists in the page.
   *
   * @param string $text
   *   The html that is expected to be found.
   *
   * @Then the page should contain the html text :text
   */
  public function assertHtmlText($text) {
    $actual = $this->getSession()->getPage()->getHtml();
    $regex = '/' . preg_quote($text, '/') . '/ui';
    $message = sprintf('The text "%s" was not found anywhere in the text of the current page.', $text);

    Assert::assertRegExp($regex, $actual, $message);
  }

  /**
   * Checks that a given text appears a certain number of times.
   *
   * @param string $text
   *   The text that should have a particular number of appearances.
   * @param int $count
   *   The number of appearances.
   *
   * @Then the text :text should appear :count time(s)
   */
  public function assertTextCount(string $text, int $count): void {
    $xpath = '//*[contains(text(), "' . $text . '")]';
    $this->assertSession()->elementsCount('xpath', $xpath, $count);
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
   * Changes the membership state of a user within a collection or solution.
   *
   * Use this to e.g. test approval of pending memberships, or blocking users.
   *
   * @param string $rdf_entity
   *   The label of the collection or solution of which the user is a member.
   * @param string $rdf_entity_bundle
   *   The RDF entity bundle, either 'collection' or 'solution'.
   * @param string $state
   *   The new membership state, one of 'active', 'pending' or 'blocked'.
   *
   * @throws \Exception
   *   Thrown when a collection or solution with the given label doesn't exist.
   *
   * @Given my membership state in the :rdf_entity :rdf_entity_bundle changes to :state
   */
  public function updateGroupState($rdf_entity, $rdf_entity_bundle, $state) {
    $entity = $this->getRdfEntityByLabel($rdf_entity, $rdf_entity_bundle);
    if (!$entity) {
      throw new \Exception("No entity found with label $rdf_entity");
    }

    $current_user = $this->getUserManager()->getCurrentUser();
    $account = User::load($current_user->uid);

    $this->subscribeUserToGroup($account, $entity, [], $state);
  }

  /**
   * Checks that the logged in user has the given OG roles in the given group.
   *
   * If the user has more than the required roles, they might have permissions
   * from the rest of the roles that will lead the test to a false positive.
   * For this reason, we request check for the specific roles passed.
   *
   * @param \Drupal\og\Entity\OgRole[] $roles
   *   An array of roles to check.
   * @param \Drupal\joinup_group\Entity\GroupInterface $group
   *   The group that is checked if the user has the role.
   *
   * @return bool
   *   Returns TRUE if the current logged in user has this role (or roles).
   *
   * @see \Drupal\DrupalExtension\Context\RawDrupalContext::loggedInWithRole
   */
  protected function loggedInWithOgRoles(array $roles, GroupInterface $group) {
    if ($this->getUserManager()->currentUserIsAnonymous() || !$this->loggedIn()) {
      return FALSE;
    }
    $current_user = $this->getUserManager()->getCurrentUser();
    $user = \Drupal::entityTypeManager()->getStorage('user')->loadUnchanged($current_user->uid);
    $membership = $group->getMembership((int) $user->id());
    if (empty($membership)) {
      return FALSE;
    }
    $expected_roles_ids = array_map(function (OgRoleInterface $role): string {
      return $role->id();
    }, $roles);
    $actual_roles_ids = $membership->getRolesIds();

    sort($expected_roles_ids);
    sort($actual_roles_ids);

    return $expected_roles_ids === $actual_roles_ids;
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
    }

    // Submit form, waiting for Honeypot protection delay to pass.
    /** @var \Drupal\Tests\honeypot\Behat\HoneypotContext $honeypot */
    $honeypot = $this->getContext('\Drupal\Tests\honeypot\Behat\HoneypotContext');
    $honeypot->waitForTimeLimit();

    $this->getSession()->getPage()->pressButton("Create new account");

    // Get the last registered user.
    $results = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $username]);
    /** @var \Drupal\user\UserInterface $user */
    $user = reset($results);

    if ($user) {
      // Track user for auto delete in tear-down.
      $this->getUserManager()->addUser((object) [
        'name' => $username,
        'uid' => $user->id(),
      ]);
    }
    else {
      throw new \Exception('User not registered.');
    }
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
    $available_options = $this->getSelectOptions($field);
    $options = $this->explodeCommaSeparatedStepArgument($options);

    if (array_intersect($options, $available_options) !== $options) {
      throw new \Exception("Options are not included in select.");
    }
  }

  /**
   * Checks if the given select field does not contain any of the given values.
   *
   * @param string $select
   *   The name of the field element.
   * @param string $options
   *   The options to be checked separated by comma.
   *
   * @throws \Exception
   *    Throws an exception when a value exists.
   *
   * @Then (the ):select field should not contain the :options option(s)
   */
  public function checkSelectDoesNotContainOptions($select, $options) {
    $field = $this->findSelect($select);
    $available_options = $this->getSelectOptions($field);
    $options = $this->explodeCommaSeparatedStepArgument($options);

    $intersection = array_intersect($available_options, $options);

    if (!empty($intersection)) {
      throw new \Exception("The select '{$select}' should not contain the options: " . implode(', ', $intersection));
    }
  }

  /**
   * Asserts the list of available options in a select box.
   *
   * @param string $select
   *   The name of the field element.
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The available list of options.
   *
   * @throws \Exception
   *    Throws an exception when the select is not found or options are not
   *    identical.
   *
   * @Then the :select select should contain the following options:
   */
  public function assertSelectOptionsAsList($select, TableNode $table) {
    $field = $this->findSelect($select);
    $this->assertSelectAvailableOptions($field, $table);
  }

  /**
   * Checks that a select field has exclusively the provided options.
   *
   * @param string $select
   *   The name of the select element.
   * @param string $options
   *   A comma-separated list of options to be present.
   *
   * @Then the available options in the :select select should be :options
   */
  public function assertSelectOptions($select, $options) {
    $field = $this->findSelect($select);
    $available_options = $this->getSelectOptions($field);
    sort($available_options);

    $options = $this->explodeCommaSeparatedStepArgument($options);
    sort($options);

    // The PHPUnit assertion will allow to understand easily which values are
    // missing and which one are extra.
    Assert::assertEquals($options, $available_options, "The '{$select}' select options don't match the wanted ones.");
  }

  /**
   * Checks that a select field does not have any of the given options.
   *
   * @param string $select
   *   The name of the select element.
   * @param string $options
   *   A comma separated list of items to check.
   *
   * @Then the available options in the :select select should not include the :options( options)
   */
  public function assertSelectOptionNotAvailable($select, $options) {
    $field = $this->findSelect($select);
    $available_options = $this->getSelectOptions($field);
    $options = $this->explodeCommaSeparatedStepArgument($options);

    Assert::assertEmpty(array_intersect($available_options, $options), "The '{$select}' select options include at least one of the given values.");
  }

  /**
   * Checks that the given select field has the given optgroups.
   *
   * @param string $select
   *   The name of the field element.
   * @param string $optgroups
   *   The optgroups to be checked separated by comma.
   *
   * @Then (the ):select field should contain the :optgroups option group(s)
   */
  public function assertSelectContainsOptgroups($select, $optgroups) {
    $field = $this->findSelect($select);
    $available_optgroups = array_values($this->getSelectOptgroups($field));
    $options = $this->explodeCommaSeparatedStepArgument($optgroups);

    foreach ($options as $option) {
      Assert::assertContains($option, $available_optgroups, "The '{$select}' select doesn't contain the option '{$option}''.", TRUE);
    }
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
   * Navigates to the content overview, a.k.a. the "Keep up to date" page.
   *
   * @When I visit the content overview( page)
   */
  public function visitContentOverviewPage(): void {
    $this->visitPath('/keep-up-to-date');
  }

  /**
   * Navigates to the edit or delete form of an entity.
   *
   * @param string $action
   *   The action. Either 'edit' or 'delete'.
   * @param string $title
   *   The title of the entity.
   * @param string $bundle
   *   An alias of a bundle as defined in the
   *   \Drupal\joinup\Traits\EntityTrait::bundleAliases method.
   *
   * @When I go to the :action form of the :title :bundle
   * @When I visit the :action form of the :title :bundle
   */
  public function goToEntityForm(string $action, string $title, string $bundle): void {
    $this->visitEntityForm($action, $title, $bundle);
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
   * @When I go to the content page of the type :type with the title :title
   * @When I visit the content page of the type :type with the title :title
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function visitNodePage($type, $title) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, $type);
    $this->visitPath($node->toUrl()->toString());
  }

  /**
   * Navigates to the revisions form of a node.
   *
   * @param string $title
   *   The title of the node.
   *
   * @When I visit the revisions page for :title
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function visitRevisionsForm(string $title): void {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getNodeByTitle($title);
    $this->visitPath($node->toUrl('version-history')->toString());
  }

  /**
   * Navigates to the dashboard page of the user.
   *
   * @When I go to the dashboard
   * @When I go to my dashboard
   */
  public function visitDashboard() {
    $this->visitPath('/dashboard');
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
      ->accessCheck(FALSE)
      ->condition('nid', $node->id())
      ->count()
      ->execute();

    Assert::assertEquals($count, $revision_count);
  }

  /**
   * Checks if the given node has the correct publication state.
   *
   * @param string $title
   *   The title of the node.
   * @param string $state
   *   The expected publication state, either 'published' or 'unpublished'.
   *
   * @Then the community content with title :title should have the publication state :state
   */
  public function assertPublicationState($title, $state) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getEntityByLabel('node', $title);
    Assert::assertSame($node->isPublished(), $state === 'published');
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
   * @throws \Exception
   *   Thrown when a logo could not be uploaded, e.g. because the file could not
   *   be found.
   *
   * @BeforeNodeCreate
   */
  public function massageFieldsBeforeNodeCreate(BeforeNodeCreateScope $scope) {
    $node = $scope->getEntity();

    // Replace field label aliases with the actual field names.
    foreach (get_object_vars($node) as $alias => $value) {
      $name = self::getNodeFieldNameFromAlias($node->type, $alias);
      if ($name !== $alias) {
        unset($node->$alias);
        // Don't set empty values, since we can have multiple aliases that point
        // to the same field (e.g. 'collection' and 'solution' both use the same
        // 'og_audience' field).
        if (!empty($value)) {
          $node->$name = $value;
        }
      }
      // Add the body field format.
      if ($name === 'body') {
        $value_key = "{$name}:value";
        $format_key = "{$name}:format";
        $node->{$value_key} = $value;
        $node->{$format_key} = 'content_editor';
        unset($node->{$name});
      }
    }

    if (!empty($node->field_attachment)) {
      // We want to copy it from the fixtures into the file system and register
      // it in the CustomPageContext so it can be cleaned up after the scenario
      // ends. Perform a small dance to get access to the context class from
      // inside this static callback.
      /** @var \Behat\Behat\Context\Environment\InitializedContextEnvironment $environment */
      $environment = $scope->getEnvironment();
      /** @var \Drupal\joinup\Context\CustomPageContext $context */
      $context = $environment->getContext(self::class);
      foreach ($context->explodeCommaSeparatedStepArgument($node->field_attachment) as $filename) {
        // The file is tracked by its filename so the value still remains to be
        // the filenames instead of the url.
        $context->createFile($filename);
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
    self::convertObjectPropertyValues($node, 'status', [
      'unpublished' => 0,
      'published' => 1,
    ]);

    // Replace human readable workflow states with machine names.
    if (!empty($node->field_state)) {
      $node->field_state = self::translateWorkflowStateAlias($node->field_state);
    }

    if (property_exists($node, 'visit_count')) {
      $node->visit_count = MetaEntity::create([
        'type' => 'visit_count',
        'count' => (int) $node->visit_count,
      ]);
    }

    if (property_exists($node, 'published_at') && !empty($node->published_at)) {
      // If the entry is not numeric, then it means that the scenario is using a
      // human readable version of the timestamp.
      // Example: Sun, 01 Dec 2019 13:00:00 +0100.
      // This will allow dynamic tests as well. `strtotime` will also be able to
      // receive entries like "1 day ago" or "+1 month".
      if (!is_numeric($node->published_at)) {
        $node->published_at = strtotime($node->published_at);
      }
    }

    $image_fields = [
      'field_document_logo',
      'field_event_logo',
      'field_news_logo',
    ];
    foreach ($image_fields as $image_field) {
      if (!empty($node->$image_field)) {
        $file = $this->createFile($node->$image_field);
        $node->$image_field = $file->getFileUri();
      }
    }

    if (isset($node->field_paragraphs_body)) {
      $paragraph = Paragraph::create([
        'type' => 'simple_paragraph',
        'field_body' => [
          'value' => $node->field_paragraphs_body,
          'format' => 'content_editor',
        ],
      ]);
      $paragraph->save();
      $node->{"field_paragraphs_body:target_id"} = $paragraph->id();
      $node->{"field_paragraphs_body:target_revision_id"} = $paragraph->getRevisionId();
      unset($node->field_paragraphs_body);
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
      'custom_page' => [
        'attachments' => 'field_attachment',
        'body' => 'field_paragraphs_body',
        'logo' => 'field_custom_page_logo',
      ],
      'discussion' => [
        'attachments' => 'field_attachment',
        'content' => 'body',
        'keywords' => 'field_keywords',
        'publication date' => 'published_at',
        'state' => 'field_state',
      ],
      'document' => [
        'document publication date' => 'field_document_publication_date',
        'document type' => 'field_type',
        'file' => 'field_file',
        'keywords' => 'field_keywords',
        'licence' => 'field_licence',
        'logo' => 'field_document_logo',
        'publication date' => 'published_at',
        'short title' => 'field_short_title',
        'spatial coverage' => 'field_document_spatial_coverage',
        'state' => 'field_state',
      ],
      'event' => [
        'agenda' => 'field_event_agenda',
        'end date' => 'field_event_date:end_value',
        'keywords' => 'field_keywords',
        'location' => 'field_location',
        'logo' => 'field_event_logo',
        'online location' => 'field_event_online_location',
        'organisation' => 'field_organisation',
        'publication date' => 'published_at',
        'scope' => 'field_scope',
        'short title' => 'field_short_title',
        'spatial coverage' => 'field_event_spatial_coverage',
        'start date' => 'field_event_date:value',
        'state' => 'field_state',
        'web url' => 'field_event_web_url',
      ],
      'glossary' => [
        'definition' => 'field_glossary_definition:value',
        'summary' => 'field_glossary_definition:summary',
        'synonyms' => 'field_glossary_synonyms',
      ],
      'news' => [
        'headline' => 'field_news_headline',
        'keywords' => 'field_keywords',
        'logo' => 'field_news_logo',
        'publication date' => 'published_at',
        'spatial coverage' => 'field_news_spatial_coverage',
        'state' => 'field_state',
      ],
      'shared' => [
        'collection' => OgGroupAudienceHelperInterface::DEFAULT_FIELD,
        'shared on' => 'field_shared_in',
        'solution' => OgGroupAudienceHelperInterface::DEFAULT_FIELD,
        'topic' => 'field_topic',
        'visits' => 'visit_count',
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
   * Example: When I press "Sign In" at "Fieldset"
   * Example: And I press "Sign In" at "Custom widget".
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
   * Prints system message from the page for debugging.
   *
   * @Then /^print messages$/
   */
  public function printMessages() {
    $selector = $this->getDrupalSelector('message_selector');
    /** @var \Behat\Mink\Element\NodeElement $message */
    foreach ($this->getSession()->getPage()->findAll("css", $selector) as $message) {
      echo $message->getText() . "\n\n";
    }
  }

  /**
   * Fills a date or time field at a datetime widget.
   *
   * Examples:
   * - When I fill in "Start date" with the date "2016-08-29".
   * - When I fill in "Start date" with the date "+2 days".
   * - When I fill in "Start date" with the time "26:59:00".
   * - When I fill in "Start date" with the time "+1 hour".
   *
   * @param string $field_group
   *   The field component's label.
   * @param string $date_component
   *   The field to be filled.
   * @param string $value
   *   The value of the field. This should either be in the format "DD-MM-YYYY"
   *   for dates, "HH:MM:SS" for time, or a relative date format.
   *
   * @throws \Exception
   *    Thrown when more than one elements match the given field in the given
   *    field group.
   *
   * @When I fill in :field_group with the :date_component :value
   */
  public function fillDateField(string $field_group, string $date_component, string $value): void {
    $is_relative_value = $date_component === 'date' ? preg_match('/^\d{2}-\d{2}-\d{4}$/', $value) === 0 : preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 0;

    if ($is_relative_value) {
      $value = date('Y-m-d', strtotime($value));
    }

    $field_selectors = $this->findDateFields($field_group);
    if (count($field_selectors) > 1) {
      throw new \Exception("More than one elements were found.");
    }
    $field_selector = reset($field_selectors);
    $field_selector->fillField(ucfirst($date_component), $value);
  }

  /**
   * Fills the date or time component of a date sub-field in a date range field.
   *
   * @param string $field
   *   The date range field name.
   * @param string $component
   *   The sub-field component. Either "date" or "time".
   * @param string $value
   *   The field value.
   * @param string $date
   *   (optional) The sub-field name. Either "start" or "end". If left empty, it
   *   is assumed that the field is a simple datetime field and not a range,
   *   thus, the date or time components are looked in the whole field.
   *
   * @throws \Exception
   *   Thrown when the date range field is not found.
   *
   * @When I fill the :date :component of the :field widget with :value
   */
  public function fillDateRangeField($field, $component, $value, $date = NULL) {
    $element = $this->findDateRangeComponent($field, $component, $date);
    $element->setValue($value);
  }

  /**
   * Clears the date or time component of date sub-field in a date range field.
   *
   * @param string $field
   *   The date range field name.
   * @param string $component
   *   The sub-field component. Either "date" or "time".
   * @param string $date
   *   (optional) The sub-field name. Either "start" or "end". If left empty, it
   *   is assumed that the field is a simple datetime field and not a range,
   *   thus, the date or time components are looked in the whole field.
   *
   * @throws \Exception
   *   Thrown when the date range field is not found.
   *
   * @When I clear the :date :component of the :field widget
   * @When I clear the :component of the :field widget
   */
  public function clearDateRangeField($field, $component, $date = NULL) {
    $element = $this->findDateRangeComponent($field, $component, $date);
    $element->setValue('');
  }

  /**
   * Asserts that a date/time field is prefilled with the current time.
   *
   * @param string $field
   *   The date/time field name.
   *
   * @throws \Exception
   *   When the field is not found.
   *
   * @Then I see :field filled with the current time
   */
  public function assertDateTimeFieldValue(string $field): void {
    $now = new \DateTimeImmutable();

    $date_part = $this->findDateRangeComponent($field, 'date')->getAttribute('value');
    $time_part = $this->findDateRangeComponent($field, 'time')->getAttribute('value');
    if (empty($date_part) || empty($time_part)) {
      throw new ExpectationFailedException("The '{$field}' date/time field is not filled with the current time.");
    }

    $date = new \DateTimeImmutable("{$date_part} {$time_part}");

    $interval = $now->diff($date);
    // Given that such a test cannot do an exact assertion, because of the
    // testing environment latency, we consider 3 seconds as an acceptable
    // fuzziness.
    if ($interval->f > 3000) {
      throw new ExpectationFailedException("The '{$field}' date/time field is not filled with the current time.");
    }
  }

  /**
   * Finds a datetime field.
   *
   * First, get the fields that use a datetime widget. Fields of type 'created'
   * also use the timestamp widget so this method must be able to handle them
   * too. Datetime fields have use a complex widget and render their title as a
   * simple header, not as a label for the field.
   *
   * @param string $field
   *   The field name.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The elements found.
   *
   * @throws \Exception
   *   Thrown when the field was not found.
   */
  public function findDateFields($field) {
    $field_selectors = $this->getSession()->getPage()->findAll('css', '.field--widget-datetime-timestamp');
    $field_selectors = array_filter($field_selectors, function ($field_selector) use ($field) {
      return $field_selector->has('named', ['content', $field]);
    });
    if (empty($field_selectors)) {
      throw new \Exception("Date field {$field} was not found.");
    }
    return $field_selectors;
  }

  /**
   * Checks, that the given date time field contains the requested text.
   *
   * Example: Then the "date" date field should contain the datetime
   * "31-1-1222 22:22:22".
   *
   * The format of the datetime is "DD-MM-YYYY HH:mm:ii"
   *
   * @param string $field
   *   The field name.
   * @param string $value
   *   The value that is checked to be found.
   *
   * @throws \Exception
   *   Thrown when more than one fields were found or when the field does not
   *   contain the required text.
   *
   * @Then the :field date field should contain the datetime :value
   */
  public function assertDateFieldContains($field, $value) {
    $elements = $this->findDateFields($field);
    if (count($elements) > 1) {
      throw new \Exception("More than one elements were found.");
    }
    $element = reset($elements);
    $date = $element->findField('Date')->getValue();
    $time = $element->findField('Time')->getValue();
    Assert::assertEquals(trim($date . ' ' . $time), $value);
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
   * @throws \Exception
   *   Thrown when the node doesn't exist or doesn't have a state field.
   *
   * @Then the :title :type content should have the :state state
   */
  public function assertNodeWorkflowState(string $title, string $type, string $state): void {
    /** @var \Drupal\joinup_workflow\EntityWorkflowStateInterface $node */
    $node = $this->getNodeByTitle($title, $type);
    $actual = $node->getWorkflowState();
    Assert::assertEquals($state, $actual, "The $title $type content has the expected state '$state' (actual: '$actual')");
  }

  /**
   * Changes the workflow state of a given entity.
   *
   * @param string $title
   *   The title of the entity to update.
   * @param string $entity_type
   *   The entity type.
   * @param string $state
   *   The workflow state.
   *
   * @throws \Exception
   *   Thrown when the entity doesn't exist, doesn't have a state field, or
   *   cannot be saved.
   *
   * @Given the workflow state of the :title :entity_type is changed to :state
   */
  public function updateWorkflowState(string $title, string $entity_type, string $state): void {
    $entity_type = self::translateEntityTypeAlias($entity_type);
    /** @var \Drupal\joinup_workflow\EntityWorkflowStateInterface|\Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getEntityByLabel($entity_type, $title);
    $entity->setWorkflowState($state);

    // Only create a new revision if the entity type supports it.
    if ($entity->getEntityType()->isRevisionable()) {
      $entity->setNewRevision(TRUE);
    }
    else {
      // If the entity does not support revisioning the static cache in the
      // access control handler will become stale. Force a cache reset since the
      // changing of the workflow state might affect entity access.
      \Drupal::entityTypeManager()->getAccessControlHandler($entity->getEntityTypeId())->resetCache();
    }

    $entity->save();
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
    $results = $this->getSession()->getPage()->findAll('css', '.listing__item--tile .listing__title, article.tile h2');
    foreach ($results as $result) {
      /** @var \Behat\Mink\Element\Element $result */
      if ($result->getText() === $heading) {
        return;
      }
    }

    throw new \Exception("The tile '$heading' was not found on the page");
  }

  /**
   * Count the tiles on the page.
   *
   * @param string $number
   *   The expected number of tiles.
   *
   * @throws \Exception
   *   Thrown when the expected number of tiles is not found.
   *
   * @Then I (should )see :number tile(s)
   */
  public function assertTileCount($number) {
    $results = $this->getSession()->getPage()->findAll('css', '.listing__item--tile .listing__title, article.tile h2');
    $nr_found = count($results);
    if ($nr_found != $number) {
      throw new \Exception("Found $nr_found tiles, expected $number");
    }
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
    // We target the heading with "h2" instead of ".listing__title" because both
    // unstyled and styled tiles use h2 as element for their titles.
    $results = $this->getSession()->getPage()->findAll('css', '.listing__item--tile h2, article.tile h2');
    foreach ($results as $result) {
      /** @var \Behat\Mink\Element\Element $result */
      if ($result->getText() === $heading) {
        throw new \Exception("The tile '$heading' was found on the page");
      }
    }
  }

  /**
   * Asserts that only the expected tiles are found in the page.
   *
   * @param string $headings
   *   A comma-separated list of tile headings.
   *
   * @Then the page should show( only) the tiles :headings
   */
  public function assertUnorderedTilesPresent($headings) {
    $tiles_in_page = array_keys($this->getTiles());
    $headings = $this->explodeCommaSeparatedStepArgument($headings);

    sort($headings);
    sort($tiles_in_page);
    Assert::assertEquals($headings, $tiles_in_page, 'The found tiles mismatch the expected ones.');
  }

  /**
   * Checks that the given tiles are found in the given block.
   *
   * The weight of the tiles are also taken into account.
   *
   * Table format:
   * | Tile title 1 |
   * | Tile title 2 |
   *
   * @Then I( should) see the( following) tiles in the :region region:
   */
  public function assertTilesInBlock(TableNode $titles_table, $region) {
    $headings_in_page = array_keys($this->getTiles($region));
    $headings_expected = $titles_table->getColumn(0);
    Assert::assertEquals($headings_expected, $headings_in_page, 'The expected tiles were not found in the wanted region or were not in the proper order.');
  }

  /**
   * Checks that none of the given tiles are available on the page.
   *
   * Table format:
   * | Tile title 1 |
   * | Tile title 2 |
   *
   * @Then I( should) not see the( following) tiles in the :region region:
   */
  public function assertTilesNotPresentInBlock(TableNode $titles_table, $region) {
    try {
      // This will fail if the region does not exist. The region does not exist
      // if there is no content rendered inside.
      $headings_in_page = array_keys($this->getTiles($region));
    }
    catch (\Exception $e) {
      return;
    }

    $headings_not_expected = $titles_table->getColumn(0);
    Assert::assertEmpty(array_intersect($headings_in_page, $headings_not_expected), 'At least one tile was found in the region but should not.');
  }

  /**
   * Assert that the text is visible in a given region.
   *
   * @Then I see the text :text in the :region region
   */
  public function assertTextVisible($text, $region) {
    $actual = $this->getRegion($region)->getText();
    $actual = preg_replace('/\s+/u', ' ', $actual);
    $regex = '/' . preg_quote($text, '/') . '/ui';
    $message = sprintf('The text "%s" was not found anywhere in the text of the region "%s".', $text, $region);

    if (!(bool) preg_match($regex, $actual)) {
      throw new \Exception($message);
    }
  }

  /**
   * Checks that the given tiles are found in the correct order.
   *
   * Table format:
   * | Tile title 1 |
   * | Tile title 2 |
   *
   * @Then I( should) see the( following) tiles in the correct order:
   */
  public function assertOrderedTilesPresent(TableNode $titles_table) {
    $headings_in_page = array_keys($this->getTiles());
    $headings_expected = $titles_table->getColumn(0);
    Assert::assertEquals($headings_expected, $headings_in_page, 'The expected tiles were not found or were not in the proper order.');
  }

  /**
   * Asserts that a certain link is present in a tile.
   *
   * @param string $heading
   *   The heading of the tile.
   * @param string $link
   *   The text of the link.
   *
   * @throws \Exception
   *   Thrown when the tile or the link are not found.
   *
   * @Then I( should) see the link :text in the :heading tile
   */
  public function assertTileContainsLink($heading, $link) {
    $element = $this->getTileByHeading($heading);
    if (!$element->findLink($link)) {
      throw new \Exception("The link '$link' was not found in the tile '$heading'.");
    }
  }

  /**
   * Asserts that a certain text is present in a tile.
   *
   * @param string $heading
   *   The heading of the tile.
   * @param string $text
   *   The text to search.
   *
   * @throws \Exception
   *   Thrown when the tile is not found or the text is not found in the tile.
   *
   * @Then I( should) see the text :text in the :heading tile
   */
  public function assertTileContainsText($heading, $text) {
    $element = $this->getTileByHeading($heading);

    if (strpos(trim($element->getText()), $text) === FALSE) {
      throw new \Exception("The text '$text' was not found in the tile '$heading'.");
    }
  }

  /**
   * Asserts that a certain text is not present in a tile.
   *
   * @param string $heading
   *   The heading of the tile.
   * @param string $text
   *   The text that should not be present.
   *
   * @throws \Exception
   *   Thrown when the tile is not found or the text is found in the tile.
   *
   * @Then I( should) not see the text :text in the :heading tile
   */
  public function assertTileDoesNotContainsText($heading, $text) {
    $element = $this->getTileByHeading($heading);

    if (strpos(trim($element->getText()), $text) !== FALSE) {
      throw new \Exception("The text '$text' was found in the tile '$heading'.");
    }
  }

  /**
   * Asserts the presence of contextual links in a specific tile.
   *
   * @param string $links
   *   A comma separated list of link texts.
   * @param string $heading
   *   The heading of the tile.
   *
   * @throws \Exception
   *   Thrown when the expected contextual links are not found in the tile.
   *
   * @Then I (should )see the contextual link(s) :links in the :heading tile
   */
  public function assertContextualLinkPresentInTile($links, $heading) {
    $links = $this->explodeCommaSeparatedStepArgument($links);
    $element = $this->getTileByHeading($heading);
    $found_links = array_keys($this->findContextualLinkPaths($element));

    $not_found = array_diff($links, $found_links);
    if (!empty($not_found)) {
      throw new \Exception(sprintf("Contextual links '%s' not found in the tile '%s'", implode(', ', $not_found), $heading));
    }
  }

  /**
   * Asserts the absence of contextual links in a specific tile.
   *
   * @param string $links
   *   A comma separated list of link texts.
   * @param string $heading
   *   The heading of the tile.
   *
   * @throws \Exception
   *   Thrown when the expected contextual links are not found in the tile.
   *
   * @Then I should not see the contextual link(s) :links in the :heading tile
   */
  public function assertContextualLinkNotPresentInTile($links, $heading) {
    $links = $this->explodeCommaSeparatedStepArgument($links);
    $element = $this->getTileByHeading($heading);
    $found_links = array_keys($this->findContextualLinkPaths($element));

    $found = array_intersect($found_links, $links);
    if (!empty($found)) {
      throw new \Exception(sprintf("Unexpected contextual link '%s' found in the tile '%s'", implode(', ', $found), $heading));
    }
  }

  /**
   * Clicks a specific contextual link in a tile.
   *
   * The click is simulated by visiting the URL of the link route.
   *
   * @param string $link
   *   The text of the contextual link.
   * @param string $heading
   *   The heading of the tile.
   *
   * @throws \Exception
   *   Thrown when the expected contextual link is not found in the tile.
   *
   * @Then I click the contextual link :link in the :heading tile
   */
  public function iClickTheContextualLinkInTile($link, $heading) {
    $element = $this->getTileByHeading($heading);
    $this->clickContextualLink($element, $link);
  }

  /**
   * Asserts that the given form submission buttons are present on the page.
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

    $this->assertSubmitButtonsVisible($buttons, FALSE);

    if (!empty($count)) {
      // Only check the actual form submit buttons, ignore other buttons that
      // might be present in wysiwygs or are used to add multiple values to a
      // field.
      $actual = count($this->getSession()->getPage()->findAll('xpath', '//div[contains(concat(" ", normalize-space(@class), " "), " form-actions ")]//input[@type = "submit"]'));
      Assert::assertEquals($count, $actual);
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
   * Checks that the plus button menu is shown on the page.
   *
   * @Then I should see the plus button menu
   */
  public function assertPlusButtonMenuPresent() {
    $element = $this->getSession()->getPage()->find('css', 'div.add-content-menu ul');
    Assert::assertNotEmpty($element);
  }

  /**
   * Checks that the plus button menu is not shown on the page.
   *
   * @Then I should not see the plus button menu
   */
  public function assertPlusButtonMenuNotPresent() {
    $element = $this->getSession()->getPage()->find('css', 'div.add-content-menu ul');
    Assert::assertEmpty($element);
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
    $this->openMaterialDesignMenu($this->getSession()->getPage()->find('css', '.add-content-menu'));
  }

  /**
   * Opens the sidebar menu on JS-enabled browsers.
   *
   * @When I open the group sidebar menu
   */
  public function openGroupMenu() {
    $this->openMaterialDesignMenu($this->getSession()->getPage()->find('css', '.sidebar-menu'));
  }

  /**
   * Opens a specific MDL dropdown menu on JS-enabled browsers.
   *
   * @When I open the header local tasks menu
   */
  public function iOpenTheMdlDropdown() {
    $element = $this->getSession()->getPage()->findById('block-three-dots-menu');
    $this->openMaterialDesignMenu($element);
  }

  /**
   * Opens the account menu on JS-enabled browsers.
   *
   * @When I open the account menu
   */
  public function iOpenTheAccountMenu() {
    $this->openMaterialDesignMenu($this->getSession()->getPage()->find('css', '.login-menu'));
  }

  /**
   * Asserts that the current url is the same as the parameter.
   *
   * The difference with the steps "Then I should be on" and "Then the url
   * should match" is that the later two strip off the query string and keep
   * only the path to compare.
   *
   * @When the( current) relative url should be :url
   */
  public function relativeUrlMatches($url) {
    $current_url = $this->getSession()->getCurrentUrl();
    $current_url = str_replace($this->getMinkParameter('base_url'), '', $current_url);
    if ($current_url !== $url) {
      $message = $this->t('Url "@current" does not match expected "@expected".', [
        '@current' => $current_url,
        '@expected' => $url,
      ]);
      throw new \Exception((string) $message);
    }
  }

  /**
   * Asserts that the plus button menu is empty.
   *
   * @When the plus button menu should be empty
   */
  public function assertEmptyPlusButtonMenu() {
    if ($this->browserSupportsJavaScript()) {
      if ($this->getSession()->getPage()->findAll('xpath', '//div[contains(concat(" ", normalize-space(@class), " "), " add-content-menu ")]//li[last()]')) {
        throw new \Exception("The plus button menu is not empty.");
      }
    }
  }

  /**
   * Create comment entities.
   *
   * Table format:
   * | message      | author   | parent      | created                   |
   * | Comment body | Jane Dow | Parent node | 2017-08-21T09:08:36+02:00 |
   * | Comment body | John Doe | Parent node |                           |
   *
   * The author and parent fields are mandatory.
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
      $node = Node::load($id);

      // Discussion node-type uses its own field and comment type.
      if ($node->bundle() === 'discussion') {
        $field_name = 'field_replies';
        $comment_type = 'reply';
      }
      else {
        $field_name = 'field_comments';
        $comment_type = 'comment';
      }

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
        'comment_type' => $comment_type,
        'status' => CommentInterface::PUBLISHED,
        'uid' => $uid,
        'field_name' => $field_name,
        'entity_type' => 'node',
        'entity_id' => $id,
      ];
      $comment_values += $values;
      $comment = Comment::create($comment_values);

      // Set creation date if provided.
      if (!empty($comment_values['created'])) {
        // Convert creation date to UNIX time if needed.
        if (!is_int($comment_values['created'])) {
          $comment_values['created'] = strtotime($comment_values['created']);
        }
        $comment->setCreatedTime($comment_values['created']);
      }

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
    Assert::assertEquals($state, trim($element->getText()));
  }

  /**
   * Checks that the given workflow buttons are visible.
   *
   * @param string $buttons
   *   A comma separated list of workflow button labels.
   *
   * @Then I should see the workflow buttons :buttons
   */
  public function assertWorkflowButtons(string $buttons): void {
    $labels = $this->explodeCommaSeparatedStepArgument($buttons);
    $this->assertSubmitButtonsVisible($labels);
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
   * @throws \PHPUnit\Framework\ExpectationFailedException
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
        Assert::assertContains($description, $element->getText());
        // The description was found, stop searching.
        return;
      }
      catch (ExpectationFailedException $e) {
        // The description did not match the expected string, keep searching.
      }
    }
    throw new ExpectationFailedException("The description $description was not found for the $label field.");
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
      Assert::assertNotEquals($description, trim($element->getText()));
    }
  }

  /**
   * Asserts that a logo exists in the header.
   *
   * @Then I should see a logo on the header
   */
  public function assertExistingLogo() {
    $xpath = '//div[contains(concat(" ", normalize-space(@class), " "), " featured__logo ")]//img';
    $this->assertSession()->elementsCount('xpath', $xpath, 1);
  }

  /**
   * Asserts that a banner exists in the header.
   *
   * @Then I should see a banner on the header
   */
  public function assertExistingBanner() {
    $xpath = '//div[contains(concat(" ", normalize-space(@class), " "), " featured__outer-wrapper ")]/@style';
    $results = $this->getSession()->getPage()->find('xpath', $xpath);
    // If the preg_match get a match, it means that the background image is
    // empty.
    $match = preg_match('/background-image: url\([\'"][\'"]\)/', $results->getText());
    if ($match !== 0) {
      throw new \Exception("The banner is empty.");
    }
  }

  /**
   * Asserts that the current user's profile picture is shown in the header.
   *
   * @Then my user profile picture should be shown in the page header
   */
  public function assertCurrentUserProfilePictureVisible() {
    if ($this->getUserManager()->currentUserIsAnonymous()) {
      throw new ExpectationFailedException('No user is currently logged in.');
    }
    $user = User::load($this->getUserManager()->getCurrentUser()->uid);
    if ($user->field_user_photo->isEmpty()) {
      throw new ExpectationFailedException('The currently logged in user does not have a profile picture.');
    }
    /** @var \Drupal\file\FileInterface $photo */
    $photo = File::load($user->field_user_photo->target_id);
    $url = ImageStyle::load('profile_icon')->buildUrl($photo->getFileUri());
    $xpath = '//div[contains(concat(" ", normalize-space(@class), " "), " login-menu ")]//img[@src="' . $url . '"]';
    $this->assertSession()->elementsCount('xpath', $xpath, 1);
  }

  /**
   * Programmatically creates node revisions.
   *
   * Table format:
   * | current title            | title            | body            |
   * | <the current node title> | Revisioned title | Revisioned body |
   *
   * @param string $bundle
   *   The bundle of the node.
   * @param \Behat\Gherkin\Node\TableNode $updates_table
   *   The table with the changes to insert in the revision.
   *
   * @Given (the following ):bundle (content )revisions:
   */
  public function createNodeRevisions($bundle, TableNode $updates_table) {
    foreach ($updates_table->getColumnsHash() as $data) {
      $node = $this->getNodeByTitle($data['current title'], $bundle);
      unset($data['current title']);

      // Crete a fake node object to be able to reuse hooks that help with
      // preparing the field values.
      $fake_node = (object) $data;

      // Retain data which is relied on by the node creation hooks.
      $fake_node->type = $bundle;
      if ($node instanceof GroupContentInterface) {
        $group = $node->getGroup();
        $group_bundle = $group->bundle();
        if (!isset($fake_node->$group_bundle)) {
          $fake_node->$group_bundle = $group->label();
        }
      }

      $this->dispatchHooks('BeforeNodeCreateScope', $fake_node);
      $this->parseEntityFields('node', $fake_node);
      // Remove the type property as we cannot change that.
      unset($fake_node->type);

      // The author is handled in \Drupal\Driver\Cores\Drupal8::nodeCreate(),
      // so we have to do it manually.
      if (isset($fake_node->author)) {
        $user = user_load_by_name($fake_node->author);
        if ($user) {
          $node->uid = $user->id();
        }
        unset($fake_node->author);
      }

      // Update all fields. Magic setters will take care of everything.
      foreach (get_object_vars($fake_node) as $name => $value) {
        $node->{$name} = $value;
      }

      // Create a new revision.
      $node->setNewRevision();
      // Finally save the node.
      $node->save();
    }
  }

  /**
   * Checks if a field contains a link to a community content page.
   *
   * @param string $field
   *   The name of the field.
   * @param string $title
   *   The title of the community content page.
   *
   * @Then the :field field should contain the link to the :title page
   */
  public function assertFieldContainsLinkToPage($field, $title) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getNodeByTitle($title);
    $uri = $node->toUrl()->toString();
    $this->assertSession()->fieldValueEquals($field, $uri);
  }

  /**
   * Asserts that the default Drupal modal is opened.
   *
   * @throws \Exception
   *   Thrown when the modal doesn't open within 5 seconds.
   *
   * @Then a modal should/will open
   */
  public function waitForDrupalModalToOpen() {
    // A modal can be opened only in a JavaScript-enabled browser.
    self::assertJavaScriptEnabledBrowser();

    $result = $this->getSession()->getPage()->waitFor(5, function () {
      // Retrieve again a fresh copy of the page element at each cycle.
      $modal = $this->getSession()->getPage()->find('css', '#drupal-modal');

      return $modal && $modal->isVisible();
    });

    if (!$result) {
      throw new \Exception('The modal did not open.');
    }
  }

  /**
   * Asserts that the default Drupal modal is closed.
   *
   * @throws \Exception
   *   Thrown when the modal doesn't close within 5 seconds.
   *
   * @Then the modal should be closed
   */
  public function waitForDrupalModalToClose() {
    // A modal can be opened only in a JavaScript-enabled browser.
    self::assertJavaScriptEnabledBrowser();

    $result = $this->getSession()->getPage()->waitFor(5, function () {
      try {
        // Normally the modal should be completely removed from the DOM,
        // but sometimes the markup is kept in the DOM as hidden.
        // @see Drupal.AjaxCommands.prototype.closeDialog()
        return !$this->getSession()->getDriver()->isVisible('//*[@id = "drupal-modal"]');
      }
      catch (NoSuchElement $e) {
        // The modal has been completely removed from the DOM.
        return TRUE;
      }
      catch (StaleElementReference $e) {
        // The element went stale in the time between fetching it and actually
        // checking its visibility. The next check should launch a NoSuchElement
        // exception.
        return FALSE;
      }
    });

    if (!$result) {
      throw new \Exception('The modal did not close.');
    }
  }

  /**
   * Asserts that a certain link contains the URL of an entity.
   *
   * @param string $link
   *   The link text.
   * @param string $title
   *   The entity title.
   * @param string $type
   *   The entity type. Either 'content' or 'rdf'.
   *
   * @throws \Exception
   *   Thrown when the link is not in the page.
   *
   * @Then /^the share link "(?P<link>[^"]+)" should point to the "(?P<title>[^"]+)" (?P<type>[^" ]+)(?:| entity)$/
   */
  public function assertLinkContainsEntityUrl(string $link, string $title, string $type): void {
    if (!in_array($type, ['content', 'rdf'])) {
      throw new \InvalidArgumentException('Type can only be one of "content" and "rdf".');
    }
    $function = $type === 'content' ? 'getNodeByTitle' : 'getRdfEntityByLabel';

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->$function($title);
    $link_element = $this->getSession()->getPage()->findLink($link);

    if (!$link_element) {
      throw new \Exception("Link '{$link}' was not found in the page.");
    }

    $entity_url = $entity->toUrl()->setAbsolute()->toString();
    $href = $link_element->getAttribute('href');

    Assert::assertContains(urlencode($entity_url), $href);
  }

  /**
   * Asserts that a certain link points to the given URL.
   *
   * @param string $title
   *   The link title.
   * @param string $link
   *   The link href attribute link.
   *
   * @throws \Exception
   *   Thrown when the link is not in the page or the target href is not exactly
   *   the expected one.
   *
   * @Then the :title link should point to :link
   */
  public function assertLinkPointsToUrl(string $title, string $link): void {
    $link_element = $this->getSession()->getPage()->findLink($title);
    $href = $link_element->getAttribute('href');
    Assert::assertEquals($href, $link);
  }

  /**
   * Forces a cleanup to run for og queue worker.
   *
   * Og enqueues items to be deleted instead of deleting them directly in order
   * to counter cases like when an entity has a big amount of memberships. This
   * causes behat to bubble all memberships to the end of the suite and attempt
   * to delete them all together which causes memory issues. Thus, the following
   * method processes the queue manually after every scenario. This turns every
   * scenario to a pseudo request when it comes to og.
   *
   * @AfterScenario
   */
  public function ogCleanup(AfterScenarioScope $event) {
    /** @var \Drupal\Core\Queue\QueueWorkerInterface $worker */
    $worker = \Drupal::service('plugin.manager.queue_worker')->createInstance('og_orphaned_group_content_cron');
    $queue = \Drupal::queue('og_orphaned_group_content');

    while ($item = $queue->claimItem()) {
      $worker->processItem($item->data);
      $queue->deleteItem($item);
    }
  }

  /**
   * Assert that a given attribute is not set for a given element in a region.
   *
   * @Then I( should) not see the :tag element with the :attribute attribute set to :value in the :region( region)
   */
  public function assertRegionElementAttribute($tag, $attribute, $value, $region) {
    $regionObj = $this->getRegion($region);
    $elements = $regionObj->findAll('css', $tag);
    if (empty($elements)) {
      return;
    }
    if (!empty($attribute)) {
      foreach ($elements as $element) {
        $attr = $element->getAttribute($attribute);
        if (!empty($attr)) {
          if (strpos($attr, "$value") !== FALSE) {
            throw new \Exception(sprintf('The "%s" attribute is equal to "%s" on the element "%s" in the "%s" region on the page %s', $attribute, $value, $tag, $region, $this->getSession()->getCurrentUrl()));
          }
        }
      }
    }
  }

  /**
   * Assert that the persistent url link follows a given pattern.
   *
   * @Then the persistent url should contain :pattern
   */
  public function thePersistentUrlShouldContain($pattern) {
    $field_selector = $this->getSession()->getPage()->find('css', '.permalink');
    $href = $field_selector->getAttribute('href');
    Assert::assertContains($pattern, $href);
  }

  /**
   * Wait until a running batch job has finished.
   *
   * This will periodically check if the progress bar is still visible on the
   * page. When this is no longer shown, it means that the batch process either
   * finished or failed.
   *
   * @Given I wait for the batch job to finish
   */
  public function iWaitForTheBatchJobToFinish() {
    // The progress bar has the CSS ID '#updateprogress'.
    $this->getSession()->wait(180000, 'jQuery("#updateprogress").length === 0');
  }

  /**
   * Wait until a running pipeline batch job has finished.
   *
   * @Given I wait for the pipeline batch job to finish
   */
  public function iWaitForThePipelineBatchJobToFinish() {
    while ($this->getSession()->getPage()->find('css', 'meta[http-equiv=\'Refresh\']')) {
      $this->getSession()->reload();
    }
  }

  /**
   * Asserts that a certain link points to a specific path.
   *
   * Example: Then the link "Login" should point to "user/login"
   *
   * @param string $link
   *   The text of the link.
   * @param string $expected_href
   *   The expected path the link should point to, without trailing slashes.
   *
   * @throws \Exception
   *   Thrown when the link is not found.
   *
   * @Then the link :link should point to :expected_href
   */
  public function assertLinkHref(string $link, string $expected_href): void {
    $element = $this->getSession()->getPage()->findLink($link);

    if (!$element) {
      throw new \Exception("The link '$link' was not found in the page.");
    }

    $expected_href = trim($expected_href, '/');
    $actual_href = $element->getAttribute('href');

    // Remove the leading base URL or base path from the link href.
    if (strpos($actual_href, $GLOBALS['base_url']) === 0) {
      $actual_href = ltrim(substr($actual_href, strlen($GLOBALS['base_url'])), '/');
    }
    elseif (($base_path = trim(base_path(), '/')) && strpos($actual_href, $base_path) === 0) {
      $actual_href = trim(substr($actual_href, strlen($base_path)), '/');
    }

    Assert::assertSame($expected_href, $actual_href, "The link '$link' doesn't point to the expected path '$expected_href'.");
  }

  /**
   * Simulates picking of an autosuggestion value from a field.
   *
   * @param string $value
   *   The value to select from suggestions.
   * @param string $field
   *   The field name.
   *
   * @throws \Exception
   *   Thrown when the field is not found, the field is not an autocomplete
   *   field, when there was a failure retrieving the autocomplete suggestions
   *   or if the wanted suggestion is not found.
   *
   * @When I pick :value from the :field autocomplete suggestions
   */
  public function fillFieldWithNormalisedEntityReference($value, $field) {
    $element = $this->getSession()->getPage()->findField($field);
    if (!$element) {
      throw new \Exception("The field '$field' not found in the page.");
    }

    if (!$element->hasAttribute('data-autocomplete-path')) {
      throw new \Exception("The field '$field' is not an entity reference autocomplete field.");
    }

    // Use the Mink base uri.
    // @see ::locatePath()
    $client = new Client(['base_uri' => $this->getMinkParameter('base_url')]);
    $uri = $element->getAttribute('data-autocomplete-path') . '?' . UrlHelper::buildQuery([
      'q' => $value,
    ]);
    $response = $client->get($uri);

    if ($response->getStatusCode() !== 200) {
      throw new \Exception("Failed to obtain suggestions for field '$field'.");
    }

    $suggestions = json_decode($response->getBody()->getContents());
    if ($suggestions === NULL) {
      throw new \Exception("Failed to decode suggestions for field '$field'.");
    }

    if (!count($suggestions)) {
      throw new \Exception("No suggestions for value '$value' found in field '$field'.");
    }

    foreach ($suggestions as $suggestion) {
      if ($suggestion->label === $value) {
        $element->setValue($suggestion->value);
        return;
      }
    }

    throw new \Exception("The value '$value' was not found in the autocomplete suggestions.");
  }

  /**
   * Asserts that a certain link in a certain region points to a specific path.
   *
   * Example: Then the link "Login" in the "Header" region
   *          should point to "user/login"
   *
   * @param string $link
   *   The text of the link.
   * @param string $region
   *   The name of the region.
   * @param string $href
   *   The expected path the link should point to, without trailing slashes.
   *
   * @throws \Exception
   *   Thrown when the link is not found.
   *
   * @Then the link :link in the :region region should point to :href
   */
  public function assertRegionLinkHref($link, $region, $href) {
    $element = $this->getRegion($region)->findLink($link);

    if (!$element) {
      throw new \Exception("The link '$link' was not found in the region $region.");
    }

    $attribute = trim($element->getAttribute('href'), '/');
    Assert::assertEquals($href, $attribute, "The link '$link' in the region '$region' doesn't point to the expected path '$href'.");
  }

  /**
   * Assert that fields are present in the given region in the given order.
   *
   * @param string $fields
   *   Fields.
   * @param string $region
   *   Region.
   *
   * @throws \Exception
   *   Thrown when an expected field is not present.
   *
   * @Then the fields :fields should be correctly ordered in the region :region
   */
  public function assertFieldsPresentInOrder($fields, $region) {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    /** @var \Behat\Mink\Element\Element $regionObj */
    $regionObj = $this->getRegion($region);
    $labels = $regionObj->findAll('xpath', "/.//*[contains(concat(' ', normalize-space(@class), ' '), ' form-wrapper ')]//label");
    $labels_on_page = [];
    /** @var \Behat\Mink\Element\NodeElement $label */
    foreach ($labels as $label) {
      $labels_on_page[] = $label->getText();
    }
    $previous = -1;
    foreach ($fields as $field) {
      $key = array_search($field, $labels_on_page);
      if ($key === FALSE) {
        throw new \Exception("Could not find expected field $field in the page");
      }
      if (!($key > $previous)) {
        throw new \Exception("Field out of order: $field");
      }
      $previous = $key;
    }
  }

  /**
   * Asserts that no links marked as active are found in a certain region.
   *
   * @param string $region
   *   The name of the region.
   *
   * @throws \Exception
   *   Thrown when active links are found.
   *
   * @Then no menu items should be active in the :region menu
   * @Then no menu items should be active in the :region region
   */
  public function assertNoActiveLinksInRegion($region) {
    $links = $this->findLinksMarkedAsActive($region);

    if (!empty($links)) {
      $labels = array_map(function ($link) {
        /** @var \Behat\Mink\Element\NodeElement $link */
        return $link->getText() . ' (' . $link->getAttribute('href') . ')';
      }, $links);
      throw new \Exception("No active links were expected in the '$region' region, but the following were found: " . implode(', ', $labels));
    }
  }

  /**
   * Asserts that a specific link is marked as active in a certain region.
   *
   * @param string $text
   *   The link text.
   * @param string $region
   *   The name of the region.
   *
   * @throws \Exception
   *   Thrown when multiple or no links at all are found in the region.
   *
   * @Then :text should be the active item in the :region menu
   * @Then :text should be the active item in the :region region
   */
  public function assertActiveLinkInRegion($text, $region) {
    $links = $this->findLinksMarkedAsActive($region);

    if (empty($links)) {
      throw new \Exception("No active links found in the '$region' region.");
    }

    /** @var \Behat\Mink\Element\NodeElement[] $links */
    if (count($links) > 1) {
      $labels = array_map(function ($link) {
        /** @var \Behat\Mink\Element\NodeElement $link */
        return $link->getText() . ' (' . $link->getAttribute('href') . ')';
      }, $links);
      throw new \Exception("More than one active link found in '$region' region: " . implode(', ', $labels));
    }

    $link = reset($links);
    Assert::assertEquals($text, $link->getText());
  }

  /**
   * Asserts that a tile is marked as featured.
   *
   * @param string $heading
   *   The heading of the tile.
   *
   * @throws \Exception
   *   Thrown when the tile is not marked as featured.
   *
   * @Then the :tile tile should be marked as featured
   */
  public function assertTileMarkedAsFeatured($heading) {
    $element = $this->getTileByHeading($heading);

    if (!$element->find('css', '.listing__card.is-featured')) {
      throw new \Exception("The tile '$heading' is not marked as featured, but it should be.");
    }
  }

  /**
   * Asserts that a tile is not marked as featured.
   *
   * @param string $heading
   *   The heading of the tile.
   * @param string $type
   *   The tile bundle.
   *
   * @throws \Exception
   *   If the $type is not solution nor community content or the tile is marked
   *   as featured.
   *
   * @Then the :tile :type tile should not be marked as featured
   *
   * @todo Due to the bug described in ISAICP-4352, we have to temporary check
   * if a tile is not featured via backend, rather than checking the DOM.
   * Manually testing or running this Behat step definition Behat in test
   * locally doesn't works with DOM check, but for some obscure reasons, that
   * are very hard to be tracked, tests are failing in Continuous PHP bot.
   * Revert back the check that a tile is not featured by checking the DOM, in
   * ISAICP-4849.
   *
   * @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-4849
   * @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-4352
   */
  public function assertTileNotMarkedAsFeatured($heading, $type) {
    if (in_array($type, ['collection', 'solution'])) {
      $entity_type_id = 'rdf_entity';
    }
    elseif (in_array($type, CommunityContentHelper::BUNDLES)) {
      $entity_type_id = 'node';
    }
    else {
      throw new \Exception("Invalid bundle '$type'.");
    }
    $entity = static::getEntityByLabel($entity_type_id, $heading);

    if ($entity instanceof PinnableGroupContentInterface && $entity->isPinned()) {
      throw new \Exception("The tile '$heading' is marked as featured, but it shouldn't be.");
    }
  }

  /**
   * Asserts that an tag with the given text exists in the given region.
   *
   * This completes the assertNotRegionElementText of Markup context.
   *
   * @param string $tag
   *   The HTML tag that is being checked.
   * @param string $text
   *   The text that should be present in the HTML tag.
   * @param string $region
   *   The region to which to confine the search for the HTML tag.
   *
   * @throws \Exception
   *   Thrown when the given tag with the given text was not found in the given
   *   region.
   *
   * @see \Drupal\DrupalExtension\Context\MarkupContext::assertNotRegionElementText()
   *
   * @Then I( should) see a(n) :tag element with the text :text in the :region( region)
   */
  public function assertRegionElementText($tag, $text, $region) {
    $regionObj = $this->getRegion($region);
    $results = $regionObj->findAll('css', $tag);
    if (!empty($results)) {
      foreach ($results as $result) {
        if ($result->getText() === $text) {
          return;
        }
      }
    }
    throw new \Exception(sprintf('The text "%s" was not found in the "%s" element in the "%s" region on the page %s', $text, $tag, $region, $this->getSession()->getCurrentUrl()));
  }

  /**
   * Asserts that a tour is available on the current page.
   *
   * @Then a tour should be available
   */
  public function assertTourAvailable() {
    if (!$this->getSession()->getPage()->find('css', 'a.js-tour-start-button')) {
      throw new \Exception("No tour is available on the page.");
    }
  }

  /**
   * Asserts that a tour is not available on the current page.
   *
   * @Then a tour should not be available
   */
  public function assertTourNotAvailable() {
    if ($this->getSession()->getPage()->find('css', 'a.js-tour-start-button')) {
      throw new \Exception("Tour is available on the page but should not.");
    }
  }

  /**
   * Press a key in a field.
   *
   * Works only in JavaScript-enabled browsers.
   *
   * @param string $key
   *   The human readable name of the key to press.
   * @param string $field
   *   The field in which to press the key.
   *
   * @throws \Exception
   *   Thrown when the browser doesn't support JavaScript or when the field is
   *   not found.
   *
   * @When I hit :key( in the keyboard) on the field :field
   * @When I hit|press the :key key in the :field field
   */
  public function pressKeyInField(string $key, string $field): void {
    if (!$this->browserSupportsJavaScript()) {
      throw new \Exception('This step requires JavaScript to run.');
    }

    $element = $this->getSession()->getPage()->findField($field);
    if (!$element) {
      throw new \Exception("Could not find field '$field'");
    }

    $this->pressKeyInElement($key, $element);
  }

  /**
   * Asserts that a tile is marked as pinned.
   *
   * @param string $heading
   *   The heading of the tile.
   *
   * @throws \Exception
   *   Thrown when the tile is not marked as pinned.
   *
   * @Then the :tile tile should be marked as pinned
   */
  public function assertTileMarkedAsPinned($heading) {
    $element = $this->getTileByHeading($heading);

    if (!$element->find('css', '.icon--pin')) {
      throw new \Exception("The tile '$heading' is not marked as pinned, but it should be.");
    }
  }

  /**
   * Asserts that a tile is not marked as pinned.
   *
   * @param string $heading
   *   The heading of the tile.
   *
   * @throws \Exception
   *   Thrown when the tile is marked as pinned.
   *
   * @Then the :tile tile should not be marked as pinned
   */
  public function assertTileNotMarkedAsPinned($heading) {
    $element = $this->getTileByHeading($heading);

    if ($element->find('css', '.icon--pin')) {
      throw new \Exception("The tile '$heading' is marked as pinned, but it shouldn't be.");
    }
  }

  /**
   * Clears a field (input, textarea, select) value.
   *
   * @param string $field
   *   The field name.
   *
   * @Then I clear (the content of )the field :field
   */
  public function clearField($field) {
    $this->getSession()->getPage()->fillField($field, '');
  }

  /**
   * Asserts the order and nesting of rows of a draggable menu table.
   *
   * It requires a JavaScript browser to run as many draggable classes are set
   * by JavaScript.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The menu data.
   *
   * @Then the draggable menu table should be:
   */
  public function assertDraggableMenuTable(TableNode $table) {
    self::assertJavaScriptEnabledBrowser();

    /** @var \Behat\Mink\Element\NodeElement[] $rows */
    $rows = $this->getSession()->getPage()->findAll('css', '#menu-overview tbody tr.draggable');
    $expected = $table->getColumnsHash();
    Assert::assertSameSize($expected, $rows);

    foreach ($expected as $delta => $data) {
      $current_row = $rows[$delta];
      $title_element = $current_row->find('css', 'td:first-of-type a:not(.tabledrag-handle)');
      Assert::assertEquals($data['title'], trim($title_element->getText()));

      $menu_link = $this->getMenuLinkByTitle($data['title']);
      $parent_input = $current_row->find('css', 'input[name="links[menu_plugin_id:' . $menu_link->getPluginId() . '][parent]"]');

      if (!$parent_input) {
        throw new \Exception('Cannot find parent input field.');
      }

      // When a parent is available, the line should be indented. Find all
      // indentation elements to assert later on based on the parent value.
      $indentation = $current_row->findAll('css', 'div.indentation');

      if (!isset($data['parent']) || $data['parent'] === '') {
        Assert::assertEmpty($parent_input->getValue());
        Assert::assertCount(0, $indentation, "The menu link '{$data['title']}' should not be indented but it is.");
      }
      else {
        $parent_link = $this->getMenuLinkByTitle($data['parent']);
        Assert::assertEquals($parent_link->getPluginId(), $parent_input->getValue());
        Assert::assertNotCount(0, $indentation, "The menu link '{$data['title']}' should be indented but it's not.");
      }
    }
  }

  /**
   * Drags a table row, specified by title, one position towards a direction.
   *
   * Instead of using the mouse, it uses the keyboard keys so that precise
   * control can be achieved.
   *
   * @param string $title
   *   The title of the menu link that belongs to the row.
   * @param string $direction
   *   The direction to move the row to. One of "up", "down", "left", "right".
   *
   * @Then I drag the :title table row( to the) :direction
   */
  public function dragTitledTableRowTowardsDirection($title, $direction) {
    self::assertJavaScriptEnabledBrowser();

    $row = $this->findDraggableTableRowByTitle($title);
    $this->dragTableRowTowardsDirection($row, $direction);
  }

  /**
   * Drags a table row, specified by its position, towards a direction.
   *
   * Instead of using the mouse, it uses the keyboard keys so that precise
   * control can be achieved.
   *
   * @param string $position
   *   The position of the row in the table. First position starts at 1.
   * @param string $direction
   *   The direction to move the row to. One of "up", "down", "left", "right".
   * @param string|null $region
   *   (optional) Only search within a certain region for the given drag icon.
   *   Default to the whole page.
   *
   * @throws \Exception
   *   Thrown when the table row with the given position cannot be found in the
   *   page.
   *
   * @Then I drag the table row at position :position( to the) :direction
   * @Then I drag the table row in the :region region at position :position( to the) :direction
   */
  public function findTableRowByPositionAndDragTowardsDirection(string $position, string $direction, ?string $region = NULL): void {
    self::assertJavaScriptEnabledBrowser();

    $region = empty($region) ? $this->getSession()->getPage() : $this->getRegion($region);
    $row = $this->findDraggableTableRowByPosition((int) $position, $region);
    $this->dragTableRowTowardsDirection($row, $direction);
  }

  /**
   * Fills the last form field matching the selector with a value.
   *
   * Useful for dynamic parts of a form where a field can be added multiple
   * times, like multiple cardinality fields.
   *
   * @param string $field
   *   The field selector.
   * @param string $value
   *   The field value.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   Thrown when no field with the given selector are found.
   *
   * @Then I fill in the latest :field field with :value
   */
  public function fillLastFieldWithLabel($field, $value) {
    $page = $this->getSession()->getPage();
    $fields = $page->findAll('named', ['field', $field]);

    if (empty($fields)) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'form field', 'id|name|label|value|placeholder', $field);
    }

    /** @var \Behat\Mink\Element\NodeElement $last */
    $last = array_pop($fields);
    $last->setValue($value);
  }

  /**
   * Checks if the output is exactly the same as the contents of a given file.
   *
   * @param string $file_path
   *   The path to the file.
   *
   * @throws \Exception
   *
   * @Then the output should match the file contents of :file
   */
  public function theOutputShouldMatchTheFile($file_path) {
    if ($this->getMinkParameter('files_path')) {
      $fullPath = rtrim(realpath($this->getMinkParameter('files_path')), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file_path;
      if (is_file($fullPath)) {
        $file_path = $fullPath;
      }
    }
    $expected_content = trim(file_get_contents($file_path));
    $browser_content = trim($this->getSession()->getPage()->getContent());
    if ($expected_content !== $browser_content) {
      throw new \Exception(sprintf("The output didn't match the fixture. Expected %s, got %s.", $expected_content, $browser_content));
    }
  }

  /**
   * Fills in a link field in the UI.
   *
   * This supports multivalue fields and both the URL and Title fields. The
   * Title field is optional.
   *
   * Table format:
   * | URL                          | Title  |
   * | https://joinup.ec.europa.eu/ | Joinup |
   * | https://www.drupal.org/      | Drupal |
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   A table containing the data to enter in the fields.
   * @param string $label
   *   The label of the link field to fill in.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the table format is invalid.
   * @throws \Exception
   *   Thrown when one of the input fields is not found.
   *
   * @Then I enter the following for the :label link field:
   */
  public function fillLinkField(TableNode $table, string $label): void {
    $rows = array_map(function (array $row): array {
      return array_change_key_case($row);
    }, $table->getColumnsHash());

    $i = 0;
    foreach ($rows as $row) {
      // XPath counts elements starting with 1.
      $i++;

      if (!isset($row['url'])) {
        throw new \InvalidArgumentException('Missing URL column in link table.');
      }
      $xpath = "//input[@id=(//label[text()='$label'])[$i]/@for]";
      $url_input_element = $this->getSession()->getPage()->find('xpath', $xpath);
      if (!$url_input_element) {
        throw new \Exception("URL input field $i not found for link field with label '$label'.'");
      }
      $url_input_element->setValue($row['url']);

      // Title field is optional.
      if (!isset($row['title'])) {
        continue;
      }

      // Retrieve the ID of the title field by taking the ID of the URL field
      // and replacing the `-uri` suffix with `-title`.
      $title_input_id = preg_replace('/uri$/', 'title', $url_input_element->getAttribute('id'), -1, $count);
      if ($count !== 1) {
        throw new \Exception("Malformed HTML ID for URL input field $i for link field with label '$label'.");
      }

      $title_input_element = $this->getSession()->getPage()->findById($title_input_id);
      if (!$title_input_element) {
        throw new \Exception("Title input field $i not found for link field with label '$label'.'");
      }
      $title_input_element->setValue($row['title']);
    }
  }

  /**
   * Asserts the status of the save button in a given region or in the page.
   *
   * @param string $button
   *   The button label.
   * @param string $status
   *   The expected status. Possible values are 'enabled' and 'disabled'.
   * @param string|null $region
   *   (optional) The region name. If left empty, the whole page will be used.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the passed value for the $status variable is not an acceptable
   *   one.
   * @throws \Exception
   *   Thrown when the region or the button are not found or if the expected
   *   status does not match the actual one.
   *
   * @Then the :button button on the :region region should be :status
   * @Then the :button button should be :status
   */
  public function assertButtonStatus(string $button, string $status, ?string $region = NULL): void {
    if (!in_array($status, ['enabled', 'disabled'])) {
      throw new \InvalidArgumentException('Allowed values for status variable are "enabled" and "disabled".');
    }

    if (!empty($region)) {
      $region = $this->getRegion($region);
    }
    else {
      $region = $this->getSession()->getPage();
    }

    $expected_status = $status === 'enabled';
    $button = $this->findNamedElementInRegion($button, 'button', $region);
    $disabled = !($button->getAttribute('disabled') === 'disabled');
    Assert::assertEquals($expected_status, $disabled);
  }

  /**
   * Checks that there are exactly the given number of elements on the page.
   *
   * @param string $count
   *   The number of elements that should be present on the page.
   * @param string $element_name
   *   The human readable name of the element to find on the page.
   *
   * @Then there should be exactly :count :element_name on the page
   */
  public function assertElementCount(string $count, string $element_name): void {
    Assert::assertEquals((int) $count, count($this->getElementsMatchingElementAlias($element_name)));
  }

  /**
   * Returns selectors used to find elements with a human readable identifier.
   *
   * @param string $alias
   *   A human readable element identifier.
   *
   * @return array[]
   *   An indexed array of selectors intended to be used with Mink's `find()`
   *   methods. Each value is a tuple containing two strings:
   *   - 0: the selector, e.g. 'css' or 'xpath'.
   *   - 1: the locator.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the element name is not defined.
   */
  protected function getSelectorsMatchingElementAlias(string $alias): array {
    $elements = [
      // The various search input fields.
      [
        'names' => [
          'search bar',
          'search bars',
          'search field',
          'search fields',
        ],
        'selectors' => [
          // The site-wide search field in the top right corner.
          ['css', 'input#search-bar__input'],
          // The search field on the search result pages.
          ['css', '.form-item-keys input[type=text]'],
        ],
      ],
    ];

    foreach ($elements as $element) {
      if (in_array($alias, $element['names'])) {
        return $element['selectors'];
      }
    }

    throw new \InvalidArgumentException("No selectors are defined for the element named '$alias'.");
  }

  /**
   * Returns elements that match the given human readable identifier.
   *
   * @param string $alias
   *   A human readable element identifier.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The elements matching the identifier.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the element name is not defined.
   */
  protected function getElementsMatchingElementAlias(string $alias): array {
    $elements = [];

    foreach ($this->getSelectorsMatchingElementAlias($alias) as $selector_tuple) {
      [$selector, $locator] = $selector_tuple;
      $elements = array_merge($elements, $this->getSession()->getPage()->findAll($selector, $locator));
    }

    return $elements;
  }

  /**
   * Asserts that a paragraph containing the given text is present in the page.
   *
   * @param string $text
   *   The text that should be present in a paragraph.
   *
   * @Then I should see a paragraph containing the text :text
   */
  public function assertParagraphText(string $text): void {
    $element = $this->getSession()->getPage()->find('xpath', '//p[text() = "' . $text . '"]');
    Assert::assertNotEmpty($element);
  }

  /**
   * Asserts that the content absolute urls exist as links.
   *
   * @param \Behat\Gherkin\Node\TableNode $titles
   *   The titles of the nodes.
   *
   * @throws \Exception
   *   Thrown if any link does not exist in the page.
   *
   * @Given I should see the absolute urls of the following content entities:
   */
  public function assertContentAbsoluteUrlsAsLinkInPage(TableNode $titles): void {
    foreach ($titles->getRows() as $title) {
      $this->assertEntityUrlInSitemap('node', $title[0]);
    }
  }

  /**
   * Asserts that the rdf entity absolute urls exist as links.
   *
   * @param \Behat\Gherkin\Node\TableNode $titles
   *   The titles of the rdf entities.
   *
   * @throws \Exception
   *   Thrown if any link does not exist in the page.
   *
   * @Given I should see the absolute urls of the following RDF entities:
   */
  public function assertRdfEntityAbsoluteUrlsAsLinkInPage(TableNode $titles): void {
    foreach ($titles->getRows() as $title) {
      $this->assertEntityUrlInSitemap('rdf_entity', $title[0]);
    }
  }

  /**
   * Asserts that the content absolute urls do not exist as links.
   *
   * @param \Behat\Gherkin\Node\TableNode $titles
   *   The titles of the nodes.
   *
   * @throws \Exception
   *   Thrown if at least a link exists in the page.
   *
   * @Given I should not see the absolute urls of the following content entities:
   */
  public function assertNotContentAbsoluteUrlsAsLinkInPage(TableNode $titles): void {
    foreach ($titles->getRows() as $title) {
      $this->assertEntityUrlInSitemap('node', $title[0], FALSE);
    }
  }

  /**
   * Changes the publication date of an entity.
   *
   * @param string $title
   *   The title of the entity.
   * @param string $bundle
   *   The bundle of the entity.
   * @param string $entity_type_id
   *   The entity type id. Either 'content' or 'rdf entity' is accepted.
   * @param string $new_publication_date
   *   The new publication date.
   *
   * @throws \InvalidArgumentException
   *   Thrown when one of the parameters is not correct.
   *
   * @Given the publication date of the :title :bundle :entity_type_id is changed to :new_value
   */
  public function givenPublicationDateChanged(string $title, string $bundle, string $entity_type_id, string $new_publication_date): void {
    if (!in_array($entity_type_id, ['content', 'rdf entity'])) {
      throw new \InvalidArgumentException('Only "content" and "rdf entity" are allowed for the $entity_type_id parameter.');
    }
    $entity_type_id = $entity_type_id === 'content' ? 'node' : 'rdf_entity';
    $entity = $this->getEntityByLabel($entity_type_id, $title, $bundle);
    $publication_date = is_numeric($new_publication_date) ? ((int) $new_publication_date) : strtotime($new_publication_date);
    if (empty($new_publication_date)) {
      throw new \InvalidArgumentException("'{$new_publication_date}' could not be converted to a proper timestamp.");
    }

    $entity->set('published_at', $publication_date);
    $entity->save();
  }

  /**
   * Asserts that the absolute url of an rdf entity exists as a link.
   *
   * @param \Behat\Gherkin\Node\TableNode $titles
   *   The titles of the rdf entities.
   *
   * @throws \Exception
   *   Thrown if the link does not exist in the page.
   *
   * @Given I should not see the absolute urls of the following RDF entities:
   */
  public function assertEmptyRdfEntityAbsoluteUrlsAsLinkInPage(TableNode $titles): void {
    foreach ($titles->getRows() as $title) {
      $this->assertEntityUrlInSitemap('rdf_entity', $title[0], FALSE);
    }
  }

  /**
   * Asserts a url in the sitemap response.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $entity_label
   *   The entity label.
   * @param bool $exists
   *   (optional) Whether to assert that a link exists or not exists. Defaults
   *   to TRUE.
   *
   * @throws \Exception
   *   Thrown if the link assertion is not the expected one.
   */
  protected function assertEntityUrlInSitemap(string $entity_type_id, string $entity_label, bool $exists = TRUE): void {
    $entity = $this->getEntityByLabel($entity_type_id, $entity_label);
    $link_text = $entity->toUrl()->setAbsolute()->toString();
    $expected = '<loc>' . $link_text . '</loc>';
    $response = $this->getSession()->getPage()->getContent();
    if ($exists) {
      Assert::assertContains($expected, $response);
    }
    else {
      Assert::assertNotContains($expected, $response);
    }
  }

  /**
   * Asserts that the anchor from the url has a correct id to track in the page.
   *
   * There is no good way to track if the page has navigated to the anchor
   * requested as this is a browser side functionality. What we _can_ assert is
   * that the anchor that is requested from the URL exists as an element id in
   * the page.
   *
   * @throws \Exception
   *   Thrown when the url does not include an anchor or the anchor is not found
   *   in the page.
   *
   * @When the page should point to the anchor from the URL
   */
  public function assertCorrectAnchor(): void {
    $current_url = $this->getSession()->getCurrentUrl();
    $url_parts = UrlHelper::parse($current_url);
    if (empty($url_parts['fragment'])) {
      throw new \Exception("The {$current_url} does not contain an anchor.");
    }
    $this->assertHtmlText("id=\"{$url_parts['fragment']}\"");
  }

  /**
   * Asserts that a menu link is in the active trail.
   *
   * @param string $link_label
   *   The label of the link to be tested.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the link doesn't exist.
   *
   * @Then the( menu) link :link_label is in the active trail
   */
  public function assertLinkIsInActiveTrail(string $link_label): void {
    $session = $this->getSession();
    $page = $session->getPage();
    if (!$page->findLink($link_label)) {
      throw new ElementNotFoundException($session->getDriver(), 'Link', 'label', $link_label);
    }
    $xpath = "//ul/li[contains(concat(' ', @class, ' '), ' menu-item--active-trail ')]/descendant::a/descendant-or-self::*[text()='{$link_label}']";
    if (!$page->find('xpath', $xpath)) {
      throw new ExpectationFailedException("The '{$link_label}' link is not in the active trail but it should.");
    }
  }

}
