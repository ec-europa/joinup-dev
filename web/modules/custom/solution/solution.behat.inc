<?php

/**
 * @file
 * Contains Drupal\solution\solution.behat.inc.
 */

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\EntityReferenceTrait;
use Drupal\joinup\Traits\FileTrait;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\OgTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\UtilityTrait;
use Drupal\joinup\Traits\WorkflowTrait;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og\OgRoleInterface;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Behat step definitions for testing solutions.
 */
class SolutionSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use EntityReferenceTrait;
  use FileTrait;
  use NodeTrait;
  use OgTrait;
  use RdfEntityTrait;
  use WorkflowTrait;
  use UtilityTrait;

  /**
   * Test solutions.
   *
   * @var \Drupal\rdf_entity\Entity\Rdf[]
   */
  protected $solutions = [];

  /**
   * Navigates to the add solution form.
   *
   * @param string $collection
   *   The parent collection.
   *
   * @When (I )go to the add solution form of the :collection collection
   * @When (I )visit the add solution form of the :collection collection
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
   * @When (I )go to (the homepage of )the :solution solution
   * @When (I )visit (the homepage of )the :solution solution
   */
  public function visitSolution($solution) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $solution */
    $solution = $this->getSolutionByName($solution);
    $this->visitPath($solution->url());
  }

  /**
   * Navigates to the edit form of a solution.
   *
   * @param string $solution
   *   The name of the solution.
   *
   * @When (I )go to the :solution solution edit form
   * @When (I )visit the :solution solution edit form
   */
  public function visitEditSolution($solution) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $solution */
    $solution = $this->getSolutionByName($solution);
    $path = $solution->url('edit-form');
    $this->visitPath($path);
  }

  /**
   * Creates a number of solutions with data provided in a table.
   *
   * Table format:
   * @codingStandardsIgnoreStart
   * | title        | description            | state                                                              | documentation | closed | creation date    | elibrary creation | moderation | modification date | landing page               | webdav creation | webdav url                  | wiki                        |
   * | Foo solution | This is a foo solution | draft|proposed|validated|deletion request|needs update|blacklisted | text.pdf      | yes    | 28-01-1995 12:05 | no                | yes        |                   | http://foo-url-example.com | yes             | http://joinup.eu/foo/webdav | http://foo-wiki-example.com |
   * | Bar solution | This is a bar solution | validated                                                          | text.pdf      | no     | 28-01-1995 12:06 | yes               | no         |                   | http://bar-url-example.com | no              |                             | http://bar-wiki-example.com |
   * @codingStandardsIgnoreEnd
   *
   * Fields title, description, state and elibrary creation are mandatory.
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
      };

      $values = $this->convertValueAliases($values);

      $this->createSolution($values);
    }
  }

  /**
   * Creates a solution with data provided in a table.
   *
   * Table format:
   * | title               | Sample solution                         |
   * | description         | A sample solution                       |
   * | banner              | banner.png                              |
   * | logo                | logo.jpg                                |
   * | moderation          | no|yes                                  |
   * | closed              | no|yes                                  |
   * | collection          | Example collection                      |
   * | contact information | Grahame Paterson, Aulay MacFarlane      |
   * | documentation       | text.pdf                                |
   * | elibrary creation   | facilitators|members|registered users   |
   * | landing page        | http://example.com/foobar/landing       |
   * | language            | Greek, German                           |
   * | owner               | Zapsum, Holdline Accountants            |
   * | policy domain       | Demography, E-inclusion                 |
   * | related solutions   | Solution 2, Solution 3                  |
   * | solution type       | [ABB159] Service Discovery Service, ... |
   * | spatial coverage    | Denmark, Serbia                         |
   * | state               | validated|...                           |
   * | status              | Under development                       |
   * | webdav creation     | no|yes                                  |
   * | webdav url          | http://joinup.eu/solution/foobar/webdav |
   * | wiki                | http://example.wiki/foobar/wiki         |
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
    };

    $values = $this->convertValueAliases($values);

    $this->createSolution($values);
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
    $storage = \Drupal::entityManager()->getStorage('rdf_entity');
    $storage->getGraphHandler()->resetRequestGraphs();
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
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   A new solution entity.
   *
   * @throws \Exception
   *   Thrown when a given image is not found.
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

    $values['rid'] = 'solution';
    $solution = Rdf::create($values);
    $solution->save();
    $this->solutions[$solution->id()] = $solution;

    // If a collection is affiliated, update the collection. In ADMS-AP the
    // reference between a solution and a collection is backwards.
    if (!empty($values['field_ar_affiliates'])) {
      foreach ($values['field_ar_affiliates'] as $collection_label) {
        $collection = $this->getRdfEntityByLabel(trim($collection_label), 'collection');
        solution_update_collection_affiliation($solution->id(), $collection->id());
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
   * @When (I )delete the :solution solution
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
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   The solution.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a solution with the given name does not exist.
   */
  protected function getSolutionByName($title) {
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
  public function assertSolutionCount($number) {
    $actual = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', 'solution')
      ->count()
      ->execute();
    if ($actual != $number) {
      throw new \Exception("Wrong number of solutions. Expected number: $number, actual number: $actual.");
    }
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
   * | solution          | user | roles                      |
   * | Sample solution A | John | facilitator, administrator |
   * | Sample solution B | Jack |                            |
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
      // Load group.
      $group = $this->getSolutionByName($values['solution']);

      // Load member.
      $member = user_load_by_name($values['user']);
      if (empty($member)) {
        throw new \Exception('User ' . $values['user'] . ' not found.');
      }

      // Convert role names to role IDs.
      $roles = [];
      if (!empty($values['roles'])) {
        $role_names = $this->explodeCommaSeparatedStepArgument($values['roles']);
        // Every owner is also a facilitator. In Joinup the facilitator role is
        // assigned to the owner when they create the solution. Since in this
        // step the solutions are already created, mimick this behaviour by
        // making sure every owner also has the 'facilitator' role.
        if (in_array('owner', $role_names) && !in_array('facilitator', $role_names)) {
          $role_names[] = 'facilitator';
        }
        $roles = $this->getOgRoles($role_names, $group);
      }

      $this->subscribeUserToGroup($member, $group, $roles);
    }
  }

  /**
   * Tracks solutions that are manually deleted and don't need cleaning up.
   *
   * @param string $solution
   *   The name of the solution.
   *
   * @Given the :solution solution will be deleted manually
   */
  public function skipCleaningOfSolution($solution) {
    unset($this->solutions[$this->getSolutionByName($solution)->id()]);
  }

  /**
   * Remove any created solutions.
   *
   * @AfterScenario
   */
  public function cleanSolutions(AfterScenarioScope $event) {
    // Remove any solutions that were created.
    foreach ($this->solutions as $solution) {
      $name = $solution->getName();
      try {
        $solution->skip_notification = TRUE;
        $solution->delete();
      }
      catch (Throwable $e) {
        // Throw a more helpful error if something goes wrong during cleanup.
        throw new \Exception(sprintf('Error while cleaning up solution "%s" after completing scenario "%s" of feature "%s": "%s"', $name, $event->getFeature()->getTitle(), $event->getScenario()->getTitle(), $e->getMessage()));
      }
    }
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
      'uri' => 'id',
      'title' => 'label',
      'affiliations requests' => 'field_is_affiliations_requests',
      'banner' => 'field_is_banner',
      'contact information' => 'field_is_contact_information',
      'creation date' => 'field_is_creation_date',
      'description' => 'field_is_description',
      'documentation' => 'field_is_documentation',
      'elibrary creation' => 'field_is_elibrary_creation',
      'keywords' => 'field_keywords',
      'landing page' => 'field_is_landing_page',
      'language' => 'field_is_language',
      'latest release' => 'field_is_latest_release',
      'logo' => 'field_is_logo',
      'metrics page' => 'field_is_metrics_page',
      'moderation' => 'field_is_moderation',
      'modification date' => 'field_is_modification_date',
      'owner' => 'field_is_owner',
      'policy domain' => 'field_policy_domain',
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
      'collection' => 'field_ar_affiliates',
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
      'field_is_elibrary_creation' =>
        ['facilitators' => 0, 'members' => 1, 'registered users' => 2],
      'field_is_webdav_creation' => ['no' => 0, 'yes' => 1],
      'field_is_state' => [
        'draft' => 'draft',
        'proposed' => 'proposed',
        'validated' => 'validated',
        'deletion request' => 'deletion_request',
        'needs update' => 'needs_update',
        'blacklisted' => 'blacklisted',
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

    // Convert any entity reference field label value with the entity ID.
    $fields = $this->convertEntityReferencesValues('rdf_entity', 'solution', $this->parseRdfEntityFields($fields));

    return $fields;
  }

  /**
   * Checks that a user has the available state options for the solution.
   *
   * The method also checks that these options are the only options available.
   *
   * Table format:
   * | solution   | user | options          |
   * | Solution A | John | Status1          |
   * | Solution B | Jack | Status1, Status2 |
   *
   * @param \Behat\Gherkin\Node\TableNode $check_table
   *   The table with the triplets solution-user-options.
   *
   * @throws \Exception
   *    Thrown when the user does not exist.
   *
   * @todo: Maybe there is a better definition available here like 'The
   * following state buttons should be available for the user on the
   * collection'.
   *
   * @Given for the following solution, the corresponding user should have the corresponding (available )state buttons:
   */
  public function verifyWorkflowStates(TableNode $check_table) {
    foreach ($check_table->getColumnsHash() as $values) {
      // Load entity.
      $solution = $this->getSolutionByName($values['solution']);

      // Load user.
      $user = user_load_by_name($values['user']);
      if (empty($user)) {
        throw new \Exception('User ' . $values['user'] . ' not found.');
      }

      // Convert states to an array.
      $states = empty($values['states']) ? [] : explode(',', $values['states']);
      $states = array_map('trim', $states);

      $this->assertAvailableTransitions($solution, $states, $user);
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
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
  }

  /**
   * Creates a simple multilingual solution.
   *
   * @Given the multilingual :title solution
   */
  public function theMultilingualSolution($title) {
    $values = [
      'rid' => 'solution',
      'label' => $title,
      'field_is_state' => 'validated',
      'field_is_description' => "English description",
    ];
    $solution = Rdf::create($values);
    $solution->save();
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
    $this->solutions[$solution->id()] = $solution;

  }

  /**
   * Applies a transition to a solution.
   *
   * @param string $username
   *   The username applying the transition.
   * @param string $entity_name
   *   The title of the solution.
   * @param string $to_state
   *   The state that the solution transits to.
   *
   * @throws \Exception
   *    An exception is thrown if the user is not found or the transition is not
   *    allowed for this user.
   *
   * @Given the user :username changes the state of the :entity_name solution to :to_state
   */
  public function applySolutionWorkflowTransition($username, $entity_name, $to_state) {
    $user = user_load_by_name($username);
    if (empty($user)) {
      throw new \Exception("User {$username} was not found.");
    }
    $solution = $this->getSolutionByName($entity_name);
    $available_states = $this->getAvailableStates($solution, $user);
    if (($transition_id = array_search($to_state, $available_states)) === FALSE) {
      throw new \Exception("The given user does not have permission to apply the specific transition.");
    }
    $transition = $solution->get('field_is_state')->first()->getWorkflow()->getTransition($transition_id);
    $solution->get('field_is_state')->first()->applyTransition($transition);
    $solution->save();
  }

}
