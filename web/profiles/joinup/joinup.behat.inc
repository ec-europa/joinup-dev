<?php

/**
 * @file
 * Contains \UserSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use Drupal\file\Entity\File;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\OgTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\WorkflowTrait;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\search_api\Entity\Server;
use Drupal\User\Entity\User;

/**
 * Behat step definitions for testing users.
 */
class JoinupSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use NodeTrait;
  use OgTrait;
  use RdfEntityTrait;
  use WorkflowTrait;

  /**
   * Test files.
   *
   * @var \Drupal\file\Entity\File[]
   */
  protected $files = [];

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
   * @param TableNode $user_table
   *   The user data.
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
    }
    $this->handleFileFields($values, 'user', 'user');

    $user = (object) $values;
    $this->userCreate($user);
  }

  /**
   * Creates and authenticates a user with the given og role(s).
   *
   * Multiple roles can be passed separated with comma.
   *
   * @Given I am logged in as a user with the :role role(s) of the :rdf_entity :rdf_entity_bundle
   * @Given I am logged in as a/an :role of the :rdf_entity :rdf_entity_bundle
   *
   * @see \Drupal\DrupalExtension\Context\DrupalContext::assertAuthenticatedByRole()
   */
  public function assertAuthenticatedByOgRole($role, $rdf_entity, $rdf_entity_bunlde) {
    $entity = $this->getRdfEntityByLabel($rdf_entity, $rdf_entity_bunlde);
    if (!$entity) {
      throw new \Exception("No entity found with label $rdf_entity");
    }
    $roles = explode(',', $role);
    $roles = array_map('trim', $roles);
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
   * Save files and change filename into a file ID.
   */
  private function handleFileFields(&$values, $entity_type, $bundle) {
    $entity_manager = \Drupal::entityManager();
    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $fields */
    $fields = $entity_manager->getFieldDefinitions($entity_type, $bundle);

    foreach ($values as $field_name => $value) {
      if (!isset($fields[$field_name])) {
        throw new \Exception("Field $field_name is not set on entity $entity_type  : $bundle");
      }
      if (empty($value)) {
        continue;
      }
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_info */
      $field_info = $fields[$field_name];
      if (in_array($field_info->getType(), ['image', 'file'])) {
        $files_path = $this->getMinkParameter('files_path');
        $path = rtrim(realpath($files_path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $value;
        if (!is_file($path)) {
          throw new \Exception("File '$value' was not found in file path '$files_path'.");
        }
        // Copy the image into the public files folder and turn it into a File
        // entity before linking it.
        $uri = 'public://' . $value;
        file_unmanaged_copy($path, $uri);
        $file = File::create(['uri' => $uri]);
        $file->save();
        $values[$field_name] = $file->get('uri')->value;

        $this->files[$file->id()] = $file;
      }
    }
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
    $storage = \Drupal::entityManager()->getStorage('rdf_entity');
    $storage->getGraphHandler()->resetRequestGraphs();
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
   * Checks that the given select field has the given options.
   *
   * To do this we extract the field text which contains the values expected
   * to be found in the select box as a concatenated string.
   * Then we check if the value is contained in the extracted value.
   *
   * @param string $select
   *    The name of the field element.
   * @param string $options
   *    The options to be checked separated by comma. Only labels are accepted.
   *
   * @throws Exception
   *    Throws an exception when a value does not exists.
   *
   * @Given (the ):select field has the :options option(s)
   */
  public function checkSelectOptions($select, $options) {
    $values = $this->getSession()->getPage()->findField($select)->getText();
    foreach (explode(',', $options) as $option) {
      if (strpos($values, trim($option)) === FALSE) {
        throw new \Exception("The option {$option} should exist in the {$select} field but was not found.");
      }
    }
  }

  /**
   * Checks if the given select field does not contain any of the given values.
   *
   * @param string $select
   *    The name of the field element.
   * @param string $options
   *    The options to be checked separated by comma.
   *
   * @throws Exception
   *    Throws an exception when a value exists.
   *
   * @Given (the ):select field does not have the :options option(s)
   */
  public function checkSelectOptionsInverse($select, $options) {
    $values = $this->getSession()->getPage()->findField($select)->getText();
    foreach (explode(',', $options) as $option) {
      if (strpos($values, trim($option)) !== FALSE) {
        throw new \Exception("The option {$option} should not exist in the {$select} field but was found.");
      }
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

      /** @var \Solarium\Client $solarium_client */
      $solarium_client = $backend->getSolrConnection();
      $update = $solarium_client->createUpdate();
      $update->addCommit(TRUE, TRUE);
      $solarium_client->update($update);
    }
  }

  /**
   * Navigates to the canonical page display of a node entity.
   *
   * @param string $bundle
   *   The type of the node page.
   * @param string $title
   *   The title of the node.
   *
   * @When (I )go to the :bundle (content ) :title edit screen
   * @When (I )visit the :bundle (content ) :title edit screen
   */
  public function visitCollection($bundle, $title) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getNodeByTitle($title, $bundle);
    $this->visitPath($node->url('edit-form'));
  }

  /**
   * Checks if a content entity is published.
   *
   * @param string $bundle
   *   The node bunlde.
   * @param string $title
   *   The node title.
   *
   * @throws \Exception
   *   Throws an exception if the content is not published.
   *
   * @Then the :title :bundle (content )should be published
   */
  public function nodePublished($bundle, $title) {
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
   *   The node bunlde.
   * @param string $title
   *   The node title.
   *
   * @throws \Exception
   *   Throws an exception if the content is not published.
   *
   * @Then the :title :bundle (content )should not be published
   */
  public function nodeNotPublished($bundle, $title) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getNodeByTitle($title, $bundle);
    if ($node->isPublished()) {
      throw new \Exception("$title content should not be published but it is.");
    }
  }

  /**
   * Replaces human readable labels and values with their real counterparts.
   *
   * This allows us to:
   * - use human readable labels like 'short title' in test scenarios, and map
   *   them to the actual field names like 'field_document_short_title'.
   * - use human readable values such as a collection name instead of the URL
   *   that is actually used in the OG reference field.
   *
   * @param \Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope $scope
   *   An object containing the entity properties and fields that are to be used
   *   for creating the node as properties on the object.
   *
   * @BeforeNodeCreate
   */
  public function massageFieldsBeforeNodeCreate(BeforeNodeCreateScope $scope) {
    $node = $scope->getEntity();

    // Replace field label aliases with the actual field names.
    foreach (get_object_vars($node) as $alias => $value) {
      $name = $this->getNodeFieldNameFromAlias($node->type, $alias);
      if ($name !== $alias) {
        unset($node->alias);
        $node->$name = $value;
      }
    }

    // Replace collection and solution group references that use titles with the
    // actual URI. Note that this fails if a URI is supplied for a collection or
    // solution in a Behat test. This is by design, the URIs are not exposed to
    // end users of the site so they should not be used in BDD scenarios.
    if (!empty($node->{OgGroupAudienceHelper::DEFAULT_FIELD})) {
      $entity = $this->getRdfEntityByLabel($node->{OgGroupAudienceHelper::DEFAULT_FIELD});
      $node->{OgGroupAudienceHelper::DEFAULT_FIELD} = $entity->id();
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
  protected function getNodeFieldNameFromAlias($type, $alias) {
    $aliases = [
      'document' => [
        'short title' => 'field_document_short_title',
      ],
      'event' => [
        'short title' => 'field_event_short_title',
        'start date' => 'field_start_date',
      ],
      'news' => [
        'kicker' => 'field_news_kicker',
        'state' => 'field_news_state',
      ],
      'shared' => [
        'collection' => OgGroupAudienceHelper::DEFAULT_FIELD,
        'solution' => OgGroupAudienceHelper::DEFAULT_FIELD,
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
   * @When I press :button at( the) :fieldset( field)
   */
  public function pressButtonInFieldset($button, $fieldset) {
    // Fixes a step argument (with \\" replaced back to ")
    // @see: Behat\MinkExtension\Context\MinkContext::fixStepArgument
    $button = str_replace('\\"', '"', $button);
    $this->getSession()->getPage()->find('named', ['fieldset', $fieldset])->pressButton($button);
  }

  /**
   * Fills a date or time field at a datetime widget.
   *
   * Example: When I fill in "Start date" with the date "29-08-2016".
   * Example: When I fill in "Start date" with the time "26:59:00".
   *
   * @param string $field_group
   *    The field component's label.
   * @param string $field_name
   *    The field to be filled.
   * @param string $value
   *    The value of the field.
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

}
