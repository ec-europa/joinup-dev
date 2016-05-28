<?php

/**
 * @file
 * Contains Drupal\solution\solution.behat.inc.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\FileTrait;
use Drupal\joinup\Traits\RandomGeneratorTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Behat step definitions for testing solutions.
 */
class SolutionSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use FileTrait;
  use RandomGeneratorTrait;
  use RdfEntityTrait;

  /**
   * Test solutions.
   *
   * @var \Drupal\rdf_entity\Entity\Rdf[]
   */
  protected $solutions = [];

  /**
   * Navigates to the add solution form.
   *
   * @When (I )go to the add solution form
   * @When (I )visit the add solution form
   */
  public function visitAddSolutionForm() {
    $this->visitPath('/rdf_entity/add/solution');
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
  public function visitCollection($solution) {
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
   * title        | description            | document | closed | creation date    | elibrary creation | moderation | modification date | landing page               | webdav creation | webdav url                  | wiki
   * Foo solution | This is a foo solution | text.pdf | yes    | 28-01-1995 12:05 | no                | yes        |                   | http://foo-url-example.com | yes             | http://joinup.eu/foo/webdav | http://foo-wiki-example.com
   * Bar solution | This is a bar solution | text.pdf | no     | 28-01-1995 12:06 | yes               | no         |                   | http://bar-url-example.com | no              |                             | http://bar-wiki-example.com
   * @codingStandardsIgnoreEnd
   *
   * Fields title, description and elibrary creation are mandatory.
   *
   * @param TableNode $solution_table
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
   * | title             | Sample solution                         |
   * | description       | A sample solution                       |
   * | document          | text.pdf                                |
   * | moderation        | no|yes                                  |
   * | closed            | no|yes                                  |
   * | elibrary creation | facilitators|members|registered users   |
   * | landing page      | http://example.com/foobar/landing       |
   * | webdav creation   | no|yes                                  |
   * | webdav url        | http://joinup.eu/solution/foobar/webdav |
   * | wiki              | http://example.wiki/foobar/wiki         |
   *
   * Fields title, description and elibrary creation are required.
   *
   * @param TableNode $solution_table
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
    if (!empty($values['field_is_documentation'])) {
      $values['field_is_documentation'] = [$this->createFile($values['field_is_documentation'], $this->getMinkParameter('files_path'))];
    }

    $values['rid'] = 'solution';
    $values['id'] = isset($values['id']) ? $values['id'] : $this->getRandomUri();
    $solution = Rdf::create($values);
    $solution->save();
    $this->solutions[$solution->id()] = $solution;

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
    $this->getSolutionByName($solution)->delete();
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
    // We cannot rely on $this->assertRdfEntityCount() because a solution shares
    // the same bundle with a release. We need to distinguish them by the
    // presence of the 'is_version_of' field - if this field is missing it is a
    // solution, if it is present it is a release.
    $actual = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', 'solution')
      ->notExists('field_is_has_version')
      ->count()
      ->execute();
    if ($actual != $number) {
      throw new \Exception("Wrong number of releases. Expected number: $number, actual number: $actual.");
    }
  }

  /**
   * Checks the number of available releases.
   *
   * @param int $number
   *   The expected number of releases.
   *
   * @throws \Exception
   *   Thrown when the number of release entities do not
   *   match the expectation.
   *
   * @Then I should have :number release(s)
   */
  public function assertReleaseCount($number) {
    // We cannot rely on $this->assertRdfEntityCount() because a release shares
    // the same bundle with a solution. We need to distinguish them by the
    // presence of the 'is_version_of' field - if this field is missing it is a
    // solution, if it is present it is a release.
    $actual = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', 'asset_release')
      ->exists('field_isr_is_version_of')
      ->count()
      ->execute();
    if ($actual != $number) {
      throw new \Exception("Wrong number of releases. Expected number: $number, actual number: $actual.");
    }
  }

  /**
   * Remove any created solutions.
   *
   * @AfterScenario
   */
  public function cleanSolutions() {
    // Remove any solutions that were created.
    foreach ($this->solutions as $solution) {
      $solution->delete();
    }
  }

  /**
   * Field alias mapping.
   *
   * @return array
   *    Mapping.
   */
  protected static function solutionFieldAliases() {
    // Mapping alias - field name.
    return [
      'uri' => 'id',
      'title' => 'label',
      'affiliations requests' => 'field_is_affiliations_requests',
      'contact information' => 'field_is_contact_information',
      'creation date' => 'field_is_creation_date',
      'description' => 'field_is_description',
      'distribution' => 'field_is_distribution',
      'documentation' => 'field_is_documentation',
      'elibrary creation' => 'field_is_elibrary_creation',
      'issue tracker' => 'field_is_issue_tracker',
      'keywords' => 'field_keywords',
      'landing page' => 'field_is_landing_page',
      'language' => 'field_is_language',
      'latest release' => 'field_is_latest_release',
      'metrics page' => 'field_is_metrics_page',
      'moderation' => 'field_is_moderation',
      'modification date' => 'field_is_modification_date',
      'owner' => 'field_is_owner',
      'related solutions' => 'field_is_related_solutions',
      'solution type' => 'field_is_solution_type',
      'source code repository' => 'field_is_source_code_repository',
      'spacial coverage' => 'field_spacial_coverage',
      'status' => 'field_status',
      'topic' => 'field_topic',
      'translation' => 'field_is_translation',
      'webdav creation' => 'field_is_webdav_creation',
      'webdav url' => 'field_is_webdav_url',
      'wiki' => 'field_is_wiki',
      'collection' => 'og_group_ref',
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
      'field_is_moderation' => ['no' => 0, 'yes' => 1],
      'field_is_elibrary_creation' =>
        ['facilitators' => 0, 'members' => 1, 'registered users' => 2],
      'field_is_webdav_creation' => ['no' => 0, 'yes' => 1],
    ];

    foreach ($fields as $field => $value) {
      if (isset($mapped_values[$field])) {
        if (!isset($mapped_values[$field][$value])) {
          throw new \Exception("Value $value is not an acceptable value for field $field.");
        }

        $fields[$field] = $mapped_values[$field][$value];
      }
    }

    // Check for the OG group.
    if (isset($fields['field_is_distribution'])) {
      $distribution = $this->getRdfEntityByLabel($fields['field_is_distribution'], 'asset_distribution');
      $fields['field_is_distribution'] = [$distribution->id()];
    }

    // Check for the OG group.
    if (isset($fields['og_group_ref'])) {
      $collection = $this->getRdfEntityByLabel($fields['og_group_ref'], 'collection');
      $fields['og_group_ref'] = $collection->id();
    }

    return $fields;
  }

}
