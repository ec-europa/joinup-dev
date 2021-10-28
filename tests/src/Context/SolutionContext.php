<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\eif\EifInterface;
use Drupal\joinup\Traits\ConfigReadOnlyTrait;
use Drupal\joinup\Traits\EntityReferenceTrait;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\FileTrait;
use Drupal\joinup\Traits\FormTrait;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\OgTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\SearchTrait;
use Drupal\joinup\Traits\TraversingTrait;
use Drupal\joinup\Traits\UserTrait;
use Drupal\joinup\Traits\UtilityTrait;
use Drupal\joinup\Traits\WorkflowTrait;
use Drupal\joinup_group\ContentCreationOptions;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og\OgRoleInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\solution\Entity\SolutionInterface;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for testing solutions.
 */
class SolutionContext extends RawDrupalContext {

  use ConfigReadOnlyTrait;
  use EntityReferenceTrait;
  use EntityTrait;
  use FileTrait;
  use FormTrait;
  use NodeTrait;
  use OgTrait;
  use RdfEntityTrait;
  use SearchTrait;
  use TraversingTrait;
  use UserTrait;
  use UtilityTrait;
  use WorkflowTrait;

  /**
   * Keep track of created entities.
   *
   * @var \Drupal\rdf_entity\RdfInterface[]
   */
  protected $rdfEntities = [];

  /**
   * Checks that the user is on the solutions overview page.
   *
   * The solutions overview page currently doesn't have a title so our common
   * practice of checking the page title falls short here.
   *
   * @Then I should be on the solutions overview page
   */
  public function assertSolutionsOverviewPage(): void {
    $this->assertSession()->addressEquals($this->locatePath('/solutions'));
  }

  /**
   * Navigates to the add solution form.
   *
   * @param string $collection
   *   The parent collection.
   *
   * @When I go to the add solution form of the :collection collection
   * @When I visit the add solution form of the :collection collection
   */
  public function visitAddSolutionForm($collection) {
    $collection = $this->getRdfEntityByLabel($collection);
    $solution_url = Url::fromRoute('solution.collection_solution.add', [
      'rdf_entity' => $collection->id(),
    ]);

    $this->visitPath($solution_url->getInternalPath());
  }

  /**
   * Navigates to the canonical page display of a solution.
   *
   * @param string $solution
   *   The name of the solution.
   *
   * @When I go to (the homepage of )the :solution solution
   * @When I visit (the homepage of )the :solution solution
   * @Given I am on (the homepage of )the :solution solution
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function visitSolution($solution) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $entity */
    $entity = $this->getSolutionByName($solution);
    $this->visitPath($entity->toUrl()->toString());
  }

  /**
   * Navigates to the solution overview page.
   *
   * @todo This is currently dependent on the Joinup profile being installed,
   *   since the view providing this overview page is exported in the profile.
   *   Remove this todo when ISAICP-5176 is fixed.
   *
   * @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-5176
   *
   * @When I visit the solution overview( page)
   */
  public function visitSolutionOverviewPage() {
    $this->visitPath('/solutions');
  }

  /**
   * Creates a number of solutions with data provided in a table.
   *
   * Table format:
   * @codingStandardsIgnoreStart
   * | title        | description            | state                                             | collection      | documentation | closed | creation date    | content creation | featured | moderation | modification date | landing page               | webdav creation | webdav url                  | wiki                        |
   * | Foo solution | This is a foo solution | draft|proposed|validated|needs update|blacklisted | Some collection | text.pdf      | yes    | 28-01-1995 12:05 | no               | yes      | yes        |                   | http://foo-url-example.com | yes             | http://joinup.eu/foo/webdav | http://foo-wiki-example.com |
   * | Bar solution | This is a bar solution | validated                                         |                 | text.pdf      | no     | 28-01-1995 12:06 | yes              | no       | no         |                   | http://bar-url-example.com | no              |                             | http://bar-wiki-example.com |
   * @codingStandardsIgnoreEnd
   *
   * Fields title and state are mandatory.
   *
   * @param \Behat\Gherkin\Node\TableNode $solution_table
   *   The solution data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )solutions:
   */
  public function givenSolutions(TableNode $solution_table) {
    $aliases = self::solutionFieldAliases();

    foreach ($solution_table->getColumnsHash() as $solution) {
      $values = [];
      // Replace the column aliases with the actual field names.
      foreach ($solution as $key => $value) {
        if (array_key_exists($key, $aliases)) {
          $values[$aliases[$key]] = $value;
        }
        else {
          throw new \Exception("Unknown column '$key' in solution table.");
        }
      }

      $this->ensureParentCollection($values);
      $values = $this->convertValueAliases($values);

      $this->createSolution($values);
    }
  }

  /**
   * Creates a solution with data provided in a table.
   *
   * @codingStandardsIgnoreStart
   * Table format:
   * | title               | Sample solution                                                |
   * | description         | A sample solution                                              |
   * | banner              | banner.png                                                     |
   * | logo                | logo.jpg                                                       |
   * | moderation          | no|yes                                                         |
   * | closed              | no|yes                                                         |
   * | collection          | Example collection                                             |
   * | contact information | Grahame Paterson, Aulay MacFarlane                             |
   * | documentation       | text.pdf                                                       |
   * | content creation    | facilitators|members|registered users                          |
   * | featured            | yes|no                                                         |
   * | landing page        | http://example.com/foobar/landing                              |
   * | language            | Greek, German                                                  |
   * | owner               | Zapsum, Holdline Accountants                                   |
   * | topic               | Demography, E-inclusion                                        |
   * | related solutions   | Solution 2, Solution 3                                         |
   * | solution type       | [ABB159] Service Discovery Service, ...                        |
   * | spatial coverage    | Denmark, Serbia                                                |
   * | state               | validated|...                                                  |
   * | status              | Under development                                              |
   * | webdav creation     | no|yes                                                         |
   * | webdav url          | http://joinup.eu/solution/foobar/webdav                        |
   * | wiki                | http://example.wiki/foobar/wiki                                |
   * | uri                 | http://data.europa.eu/w21/fc2dee5a-88fb-4aff-b3bf-9b1d66903296 |
   * @codingStandardsIgnoreEnd
   *
   * Fields "title", "description" and "state" are required.
   *
   * @param \Behat\Gherkin\Node\TableNode $solution_table
   *   The solution data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )solution:
   */
  public function givenSolution(TableNode $solution_table) {
    $aliases = self::solutionFieldAliases();

    $values = [];
    // Replace the column aliases with the actual field names.
    foreach ($solution_table->getRowsHash() as $key => $value) {
      if (array_key_exists($key, $aliases)) {
        $values[$aliases[$key]] = $value;
      }
      else {
        throw new \Exception("Unknown column '$key' in solution table.");
      }
    }

    $this->ensureParentCollection($values);
    $values = $this->convertValueAliases($values);

    $this->createSolution($values);
  }

  /**
   * Make sure that the solution being created has a parent collection.
   *
   * For some tests the solution's parent collection has no relevance. Such
   * tests are allowed to omit an explicit collection creation. We're creating
   * here a dummy parent collection just to satisfy the data integrity
   * constraint.
   *
   * @param array $values
   *   The solution creation values.
   *
   * @see \Drupal\solution\SolutionAffiliationFieldItemList::preSave()
   */
  protected function ensureParentCollection(array &$values): void {
    if (empty($values['collection'])) {
      $label = $this->getRandom()->sentences(3);
      $collection = Rdf::create([
        'rid' => 'collection',
        'label' => $label,
        'field_ar_state' => 'validated',
      ]);
      $collection->save();
      $values['collection'] = $label;
      // Track this collection.
      $this->rdfEntities[$collection->id()] = $collection;

      // Remove any links created by this collection because they might produce
      // name collisions with the solution. E.g. the left sidebar link 'About'.
      // @todo Remove this workaround in ISAICP-5597.
      // @see tests/features/custom_page/navigation_menu.feature
      // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-5597
      $entity_type_manager = \Drupal::entityTypeManager();
      $storage = $entity_type_manager->getStorage('ogmenu_instance');
      $og_menu_instance_ids = $storage->getQuery()
        ->condition('type', 'navigation')
        ->condition(OgGroupAudienceHelperInterface::DEFAULT_FIELD, $collection->id())
        ->execute();
      $storage->delete($storage->loadMultiple($og_menu_instance_ids));
    }
  }

  /**
   * Make group content part of a solution.
   *
   * Table format:
   * | content                | solution          |
   * | Content entity 1 title | Solution 1 title  |
   * | Content entity 2 title | Solution 1 title  |
   * | Content entity 3 title | Solution 2 title  |
   *
   * @param string $bundle
   *   The bundle of the content entity.
   * @param \Behat\Gherkin\Node\TableNode $membership_table
   *   The membership table.
   *
   * @Given (the following ):bundle content belong to the corresponding solutions:
   */
  public function givenContentMemberships($bundle, TableNode $membership_table) {
    // As each given step is executed in the same request
    // we need to reset the mapping.
    foreach ($membership_table->getColumnsHash() as $values) {
      $group = $this->getRdfEntityByLabel($values['solution'], 'solution');
      /** @var \Drupal\node\NodeInterface $content */
      $content = $this->getNodeByTitle($values['content'], $bundle);
      $content->get(OgGroupAudienceHelperInterface::DEFAULT_FIELD)->set(0, $group->id());
      $content->save();
    }
  }

  /**
   * Asserts that a user is an owner of the given solution.
   *
   * To be owner of a solution, a user should be an administrator and
   * a facilitator.
   *
   * @param string $username
   *   The name of the user.
   * @param string $solution
   *   The label of the group entity.
   *
   * @throws \Exception
   *    Throws an exception when the user is not found.
   *
   * @Given (the user ):username should be the owner of the :solution solution
   */
  public function assertSolutionOwnership($username, $solution) {
    $user = user_load_by_name($username);
    if (empty($user)) {
      throw new \Exception("User {$username} could not be found.");
    }

    $solution = $this->getRdfEntityByLabel($solution, 'solution');
    $owner_roles = [
      OgRoleInterface::ADMINISTRATOR,
      'facilitator',
    ];

    $this->assertOgGroupOwnership($user, $solution, $owner_roles);
  }

  /**
   * Creates a solution from the given property and field data.
   *
   * @param array $values
   *   An optional associative array of values, keyed by property name.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   A new solution entity.
   *
   * @throws \Exception
   *   Thrown when a given image is not found, or when a solution is being
   *   pinned in a non-existing group.
   */
  protected function createSolution(array $values) {
    $file_fields = [
      'field_is_documentation',
      'field_is_banner',
      'field_is_logo',
    ];

    foreach ($file_fields as $field_name) {
      if (!empty($values[$field_name])) {
        foreach ($values[$field_name] as &$filename) {
          $filename = [$this->createFile($filename)->id()];
        }
      }
    }

    // If the solution is featured we need to create the meta entity that stores
    // this information after creating the solution.
    $is_featured = in_array(strtolower((string) ($values['feature'] ?? '')), [
      'y',
      'yes',
    ]);

    /** @var \Drupal\solution\Entity\SolutionInterface $solution */
    $solution = $this->createRdfEntity('solution', $values);

    if ($is_featured) {
      $solution->feature();
    }

    $this->rdfEntities[$solution->id()] = $solution;

    if (!empty($values['pinned_in_collection'])) {
      foreach (explode(',', $values['pinned_in_collection']) as $group_label) {
        $group = self::getRdfEntityByLabel(trim($group_label));
        if (!$group instanceof GroupInterface) {
          throw new \Exception("Cannot pin solution {$solution->label()} in $group_label since it does not exist or is not a group.");
        }
        $solution->pin($group);
      }
    }

    return $solution;
  }

  /**
   * Deletes a solution.
   *
   * @param string $solution
   *   The name of the solution.
   *
   * @When I delete the :solution solution
   */
  public function deleteSolution($solution) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $solution */
    $solution = $this->getSolutionByName($solution);
    $solution->skip_notification = TRUE;
    $solution->delete();
  }

  /**
   * Returns the Solution with the given name.
   *
   * If multiple solution have the same name, the first one will be returned.
   *
   * @param string $title
   *   The solution name.
   *
   * @return \Drupal\solution\Entity\SolutionInterface
   *   The solution.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a solution with the given name does not exist.
   */
  protected function getSolutionByName($title): SolutionInterface {
    return $this->getRdfEntityByLabel($title, 'solution');
  }

  /**
   * Returns the release with the given name.
   *
   * If multiple solution have the same name, the first one will be returned.
   *
   * @param string $title
   *   The release name.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   The release.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a release with the given name does not exist.
   */
  public static function getReleaseByName($title) {
    $query = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', 'solution')
      ->exists('field_is_is_version_of')
      ->condition('label', $title)
      ->range(0, 1);
    $result = $query->execute();

    if (empty($result)) {
      throw new \InvalidArgumentException("The release with the name '$title' was not found.");
    }

    return Rdf::load(reset($result));
  }

  /**
   * Checks the number of available solutions.
   *
   * @param int $number
   *   The expected number of solutions.
   *
   * @throws \Exception
   *   Throws an exception when the expected number is not equal to the given.
   *
   * @Then I should have :number solution(s)
   */
  public function assertSolutionCount(int $number): void {
    $this->assertRdfEntityCount($number, 'solution');
  }

  /**
   * Subscribes the given users to the given solution.
   *
   * The role Id of organic group roles are consisting of 3 parts, the entity
   * type ID and the bundle of the group, and the machine name of the role.
   * Do not provide the complete ID of the Og role. The entity type ID and
   * bundle are going to be added automatically.
   *
   * Table format:
   * | solution          | user | roles                      | state   |
   * | Sample solution A | John | facilitator, administrator | active  |
   * | Sample solution B | Jack |                            | blocked |
   *
   * Only the 'solution' and 'user' columns are required. Multiple comma
   * separated roles can be passed.
   *
   * The state can be empty, or one of 'active', 'pending', or 'blocked'. If the
   * state is omitted it will default to 'active'.
   *
   * @param \Behat\Gherkin\Node\TableNode $membership_table
   *   The membership table.
   *
   * @throws \Exception
   *   Thrown when a solution is not found.
   *
   * @Given (the following )solution user membership(s):
   */
  public function givenSolutionUserMemberships(TableNode $membership_table) {
    foreach ($membership_table->getColumnsHash() as $values) {
      $group = $this->getSolutionByName($values['solution']);
      $this->givenUserMembership($group, $values);
    }
  }

  /**
   * Remove any created entities.
   *
   * @AfterScenario
   */
  public function clearEntities(AfterScenarioScope $event) {
    if (empty($this->rdfEntities)) {
      return;
    }

    // Since we might be cleaning up many solutions, temporarily disable the
    // feature to commit the index after every query.
    $this->disableCommitOnUpdate();

    // Remove any solutions that were created.
    foreach ($this->rdfEntities as $entity) {
      $name = $entity->label();
      try {
        $entity->skip_notification = TRUE;
        $entity->delete();
      }
      catch (\Throwable $e) {
        // Throw a more helpful error if something goes wrong during cleanup.
        throw new \Exception(sprintf('Error while cleaning up solution "%s" after completing scenario "%s" of feature "%s": "%s"', $name, $event->getFeature()->getTitle(), $event->getScenario()->getTitle(), $e->getMessage()));
      }
    }
    $this->rdfEntities = [];
    $this->enableCommitOnUpdate();
  }

  /**
   * Field alias mapping.
   *
   * @return array
   *   Mapping.
   */
  protected static function solutionFieldAliases() {
    // Mapping alias - field name.
    return [
      'author' => 'uid',
      'uri' => 'id',
      'title' => 'label',
      'short ID' => 'field_short_id',
      'affiliations requests' => 'field_is_affiliations_requests',
      'banner' => 'field_is_banner',
      'contact information' => 'field_is_contact_information',
      'content creation' => 'field_is_content_creation',
      'creation date' => 'created',
      'description' => 'field_is_description',
      'documentation' => 'field_is_documentation',
      'eif reference' => 'field_is_eif_recommendation',
      'eif category' => 'field_is_eif_category',
      'keywords' => 'field_keywords',
      'landing page' => 'field_is_landing_page',
      'language' => 'field_is_language',
      'latest release' => 'field_is_latest_release',
      'logo' => 'field_is_logo',
      'metrics page' => 'field_is_metrics_page',
      'moderation' => 'field_is_moderation',
      'modification date' => 'changed',
      'owner' => 'field_is_owner',
      'topic' => 'field_topic',
      'related by type' => 'field_is_show_eira_related',
      'related solutions' => 'field_is_related_solutions',
      'solution type' => 'field_is_solution_type',
      'source code repository' => 'field_is_source_code_repository',
      'spatial coverage' => 'field_spatial_coverage',
      'status' => 'field_status',
      'topic' => 'field_topic',
      'translation' => 'field_is_translation',
      'webdav creation' => 'field_is_webdav_creation',
      'webdav url' => 'field_is_webdav_url',
      'wiki' => 'field_is_wiki',
      'state' => 'field_is_state',
      'collection' => 'collection',
      'collections' => 'collection',
      'featured' => 'feature',
      'pinned in' => 'pinned_in_collection',
      'shared on' => 'field_is_shared_in',
    ];
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
      'field_is_moderation' => ['no' => 0, 'yes' => 1],
      'field_is_content_creation' => [
        'facilitators and authors' => ContentCreationOptions::FACILITATORS_AND_AUTHORS,
        'registered users' => ContentCreationOptions::REGISTERED_USERS,
      ],
      'field_is_webdav_creation' => ['no' => 0, 'yes' => 1],
      'field_is_state' => [
        'draft' => 'draft',
        'proposed' => 'proposed',
        'validated' => 'validated',
        'needs update' => 'needs_update',
        'blacklisted' => 'blacklisted',
      ],
      'field_is_show_eira_related' => ['no' => 0, 'yes' => 1],
    ];

    $eif_categories = array_flip(EifInterface::EIF_CATEGORIES);
    if (isset($fields['field_is_eif_category'])) {
      $labels = $this->explodeCommaSeparatedStepArgument($fields['field_is_eif_category']);
      $values = [];
      foreach ($labels as $label) {
        $values[] = $eif_categories[$label];
      }
      $fields['field_is_eif_category'] = implode(',', $values);
    }

    foreach ($fields as $field => $value) {
      if (isset($mapped_values[$field])) {
        if (!isset($mapped_values[$field][$value])) {
          throw new \Exception("Value $value is not an acceptable value for field $field.");
        }

        $fields[$field] = $mapped_values[$field][$value];
      }
    }

    // The solution affiliation could be multi-value.
    if (isset($fields['collection'])) {
      $fields['collection'] = $this->explodeCommaSeparatedStepArgument($fields['collection']);
    }

    // Convert any entity reference field label value with the entity ID.
    $fields = $this->convertEntityReferencesValues('rdf_entity', 'solution', $this->parseRdfEntityFields($fields));
    return $fields;
  }

  /**
   * Checks that a user has the available state options for the solution.
   *
   * The method also checks that these options are the only options available.
   *
   * This method will log in as each user in sequence, so take care to only use
   * it when the currently logged in user can be discarded.
   *
   * Table format:
   * | solution   | user | buttons         |
   * | Solution A | John | Save as draft   |
   * | Solution B | Jack | Update, Publish |
   *
   * @param \Behat\Gherkin\Node\TableNode $check_table
   *   The table with the triplets solution-user-buttons.
   *
   * @throws \Exception
   *    Thrown when the user does not exist.
   *
   * @todo Maybe there is a better definition available here like 'The
   * following state buttons should be available for the user on the
   * solution'.
   *
   * @Then for the following solution, the corresponding user should have the corresponding (available )state buttons:
   */
  public function verifyWorkflowStates(TableNode $check_table): void {
    foreach ($check_table->getColumnsHash() as $values) {
      $username = $values['user'];
      $user = $this->userManager->getUser($username);

      // Check if we are already logged in as the user to test.
      $current_username = $this->userManager->getCurrentUser()->name ?? NULL;
      if ($username !== $current_username) {
        $this->authenticationManager->logIn($user);
      }

      // Go to the edit form and check that the expected buttons are visible.
      $this->visitEntityForm('edit', $values['solution'], 'solution');
      $buttons = $this->explodeCommaSeparatedStepArgument($values['buttons']);
      $this->assertSubmitButtonsVisible($buttons);
    }
  }

  /**
   * Checks that a user has access to the delete button on the solution form.
   *
   * Table format:
   * | solution   | user | delete link |
   * | Solution A | John | yes         |
   * | Solution B | Jack | no          |
   *
   * @param \Behat\Gherkin\Node\TableNode $check_table
   *   The table with the triplets solution-user-link visibility.
   *
   * @throws \Exception
   *    Thrown when the user does not exist.
   *
   * @Then the visibility of the delete link should be as follows for these users in these solutions:
   */
  public function verifyDeleteLinkVisibility(TableNode $check_table): void {
    foreach ($check_table->getColumnsHash() as $values) {
      $user = $this->getUserByName($values['user']);
      $solution = $this->getSolutionByName($values['solution']);
      $visible = $values['delete link'] === 'yes';
      $this->assertGroupEntityOperation($visible, 'delete', $solution, $user);
    }
  }

  /**
   * Enable a given language.
   *
   * @Given the language :langcode is enabled
   */
  public function languageEnabled($langcode) {
    // Create the needed language.
    $language_manager = \Drupal::languageManager();
    if (!$language_manager->getLanguage($langcode)) {
      // Temporarily bypass the read only config functionality so that we can
      // enable the language for testing.
      $this->bypassReadOnlyConfig();
      ConfigurableLanguage::createFromLangcode($langcode)->save();
      $this->restoreReadOnlyConfig();
    }
  }

  /**
   * Creates a simple multilingual solution.
   *
   * @Given the multilingual :title solution of :collection collection
   */
  public function theMultilingualSolution(string $title, string $collection): void {
    $collection = $this->getEntityByLabel('rdf_entity', $collection, 'collection');
    $values = [
      'label' => $title,
      'field_is_state' => 'validated',
      'field_is_description' => "English description",
      'collection' => $collection->id(),
    ];
    $solution = $this->createRdfEntity('solution', $values);
    // Fill with the specific content translation fields and fall-back to
    // the remaining values from the base translation.
    $solution_values = $solution->toArray();
    $solution_values += [
      'content_translation_source' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'content_translation_status' => TRUE,
      'content_translation_uid' => 1,
      'content_translation_outdated' => FALSE,
    ];
    $solution_values['field_is_description'] = "Catalan description";
    // Create the translation.
    $solution->addTranslation('ca', $solution_values)->save();
    $this->rdfEntities[$solution->id()] = $solution;
  }

  /**
   * Checks that the given solution is affiliated with the given collection.
   *
   * @param string $collection_label
   *   The name of the collection to check.
   * @param string $solution_label
   *   The name of the solution to check.
   *
   * @throws \Exception
   *   Thrown when the solution is not affiliated with the collection.
   *
   * @Then the :solution_label solution should be affiliated with the :collection_label collection
   */
  public function assertCollectionAffiliation($collection_label, $solution_label) {
    $solution = $this->getRdfEntityByLabel($solution_label, 'solution');
    $ids = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', 'collection')
      ->condition('label', $collection_label)
      ->condition('field_ar_affiliates', $solution->id())
      ->execute();
    if (!$ids) {
      throw new \Exception("The '$solution_label' solution is not affiliated with the '$collection_label' collection but it should be.");
    }
  }

  /**
   * Checks that the given tile contains the given number of downloads.
   *
   * @param string $heading
   *   Heading of the tile.
   * @param int $count
   *   Number of the downloads.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   Thrown when the tile is not found in the page.
   *
   * @Then the :heading tile should show :count downloads
   */
  public function assertTileContainsDownloadCount(string $heading, int $count): void {
    $tile = $this->getTileByHeading($heading);
    $elements = $tile->findAll('xpath', '//span[contains(concat(" ", normalize-space(@class), " "), " stats__text ") and text() = "' . $count . '"]');
    Assert::assertCount(1, $elements);
  }

  /**
   * Checks that the given tile contains a download icon.
   *
   * @param string $heading
   *   Heading of the tile.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   Thrown when the tile is not found in the page.
   *
   * @Then the download icon should not be shown in the :heading tile
   */
  public function assertTileNotContainsDownloadIcon(string $heading): void {
    $tile = $this->getTileByHeading($heading);
    $elements = $tile->findAll('xpath', '//span[contains(concat(" ", normalize-space(@class), " "), " icon--download ")]');
    Assert::assertEmpty($elements);
  }

  /**
   * Sets the visit or download count for a given entity to a certain value.
   *
   * @param string $type
   *   The type of counter: 'download' or 'visit'.
   * @param string $target_entity_label
   *   The target entit label.
   * @param string $count
   *   The counter to be set.
   *
   * @throws \Exception
   *   When:
   *   - An invalid $type was specified.
   *   - The entity with $target_entity_label doesn't exists.
   *   - A related meta entity doesn't exist.
   *
   * @Given the :type count of :target_entity_label is :count
   */
  public function setDownloadCountForEntity(string $type, string $target_entity_label, string $count): void {
    if (!in_array($type, ['download', 'visit'])) {
      throw new \Exception("Type should be 'download' or 'visit' but '{$type}' was given.");
    }

    $target_entity_type_id = $type === 'visit' ? 'node' : 'rdf_entity';
    $field_name = "{$type}_count";
    /** @var \Drupal\Core\Entity\ContentEntityInterface $target_entity */
    $target_entity = $this->getEntityByLabel($target_entity_type_id, $target_entity_label);

    /** @var \Drupal\meta_entity\Entity\MetaEntityInterface $meta_entity */
    $meta_entity = $target_entity->{$field_name}->entity;
    if (!$meta_entity) {
      throw new \Exception("The '{$target_entity_label}' doesn't have a related meta entity.");
    }

    $meta_entity->set('count', (int) $count)->save();
  }

}
