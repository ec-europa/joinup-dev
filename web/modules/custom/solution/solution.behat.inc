<?php

/**
 * @file
 * Contains \SolutionSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\file\Entity\File;
use Drupal\og\OgGroupAudienceHelper;

/**
 * Behat step definitions for testing solutions.
 */
class SolutionSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  /**
   * Test solutions.
   *
   * @var \Drupal\rdf_entity\Entity\Rdf[]
   */
  protected $solutions = [];

  /**
   * Test images.
   *
   * @var \Drupal\file\Entity\File[]
   */
  protected $documents = [];

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
   * * @param TableNode $solution_table
   *   The solution data.
   *
   * 'contact information' => 'field_is_contact_information',
   * 'landing page' => 'field_is_landing_page',
   * 'webdav creation' => 'field_is_webdav_creation',
   * 'webdav url' => 'field_is_webdav_url',
   * 'wiki' => 'field_is_wiki'
   *
   * Table format:
   *
   * @codingStandardsIgnoreStart
   * uri                            | title         | description              | document | closed  | creation date     | elibrary creation  | moderation  | modification date | landing page               | webdav creation | webdav url                  | wiki
   * http://joinup.eu/solution/foo  | Foo solution  | This is a foo solution   | text.pdf | 1       | 28-01-1995 12:05  | 0                  | 1           |                   | http://foo-url-example.com | 1               | http://joinup.eu/foo/webdav | http://foo-wiki-example.com
   * http://joinup.eu/solution/bar  | Bar solution  | This is a bar solution   | text.pdf | 0       | 28-01-1995 12:06  | 1                  | 0           |                   | http://bar-url-example.com | 0               |                             | http://bar-wiki-example.com
   * @codingStandardsIgnoreEnd
   *
   * Fields title, description and elibrary creation are mandatory.
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
      $this->createSolution($values);
    }
  }

  /**
   * Creates a solution with data provided in a table.
   *
   * Table format:
   * | title             | Sample solution                         |
   * | uri               | http://joinup.eu/solution/foobar        |
   * | description       | A sample solution                       |
   * | document          | text.pdf                                |
   * | pre-moderation    | 0|1                                     |
   * | closed            | 0|1                                     |
   * | elibrary creation | 0|1|2                                   |
   * | landing page      | http://example.com/foobar/landing       |
   * | webdav creation   | 0|1                                     |
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
  public function givenCollection(TableNode $solution_table) {
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
    $this->createSolution($values);
  }

  /**
   * Creates a solution from the given property and field data.
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
  public function createSolution(array $values) {
    // Add image.
    if (!empty($values['field_is_documentation'])) {
      $filename = $values['field_is_documentation'];
      $files_path = $this->getMinkParameter('files_path');
      $path = rtrim(realpath($files_path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
      if (!is_file($path)) {
        throw new \Exception("Document '$filename' was not found in file path '$files_path'.");
      }
      // Copy the document into the public files folder and turn it into a File
      // entity before linking it to the collection.
      $uri = 'public://' . $filename;
      $destination = file_unmanaged_copy($path, $uri);
      $document = File::create(['uri' => $destination]);
      $document->save();
      $values['field_is_documentation'] = [$document->id()];

      $this->documents[$document->id()] = $document;
    }

    $values['rid'] = 'solution';
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
   * @param string $name
   *   The solution name.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   The solution.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a solution with the given name does not exist.
   */
  protected function getSolutionByName($name) {
    $query = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', 'solution')
      ->condition('label', $name)
      ->range(0, 1);
    $result = $query->execute();

    if (empty($result)) {
      throw new \InvalidArgumentException("The solution with the name '$name' was not found.");
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
   *   Thrown when the number of solutions does not not match the expectation.
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
   * Checks if the given solution belongs to the given collection.
   *
   * If there are multiple solutions or collections with the same name, then
   * only the first one is checked.
   *
   * @param string $solution
   *   The title of the custom page to check.
   * @param string $collection
   *   The name of the collection to check.
   *
   * @throws \Exception
   *   Thrown when a custom page with the given title doesn't exist.
   *
   * @Then the :collection collection has a solution named :solution
   */
  public function assertOgMembership($solution, $collection) {
    /** @var \Drupal\rdf_entity\RdfInterface $collection */
    $collection = \CollectionSubContext::getCollectionByName($collection);
    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    $solution = \SolutionSubContext::getSolutionByName($solution);
    // This handles the solutions added by form submit.
    if (!in_array($solution, $this->solutions)) {
      $this->solutions[$solution->id()] = $solution;
    }

    /** @var \Drupal\og\Plugin\Field\FieldType\OgStandardReferenceItem $group */
    foreach ($solution->get(OgGroupAudienceHelper::DEFAULT_FIELD) as $group) {
      if ($group->getValue()['target_id'] == $collection->id()) {
        // Test passes.
        return;
      }
    }

    throw new \Exception("The solution '{$solution->label()}' is not associated with collection '{$collection->label()}'.");
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
    // Also remove the image entities that were attached to the collections.
    foreach ($this->documents as $document) {
      $document->delete();
    }
  }

  /**
   * Field alias mapping.
   *
   * @return array
   *    Mapping.
   */
  private static function solutionFieldAliases() {
    // Mapping alias - field name.
    return [
      'uri' => 'id',
      'name' => 'label',
      'affiliations requests' => 'field_is_affiliations_requests',
      'contact information' => 'field_is_contact_information',
      'creation date' => 'field_is_creation_date',
      'description' => 'field_is_description',
      'documentation' => 'field_is_documentation',
      'elibrary creation' => 'field_is_elibrary_creation',
      'included asset' => 'field_is_included_asset',
      'issue tracker' => 'field_is_issue_tracker',
      'keywords' => 'field_is_keywords',
      'landing page' => 'field_is_landing_page',
      'language' => 'field_is_language',
      'latest release' => 'field_is_latest_release',
      'metrics page' => 'field_is_metrics_page',
      'moderation' => 'field_is_moderation',
      'modification date' => 'field_is_modification_date',
      'owner' => 'field_is_owner',
      'related solutions' => 'field_is_related_solutions',
      'solution category' => 'field_is_solution_category',
      'solution type' => 'field_is_solution_type',
      'source code repository' => 'field_is_source_code_repository',
      'spacial coverage' => 'field_is_spacial_coverage',
      'status' => 'field_is_status',
      'topic' => 'field_is_topic',
      'translation' => 'field_is_translation',
      'webdav creation' => 'field_is_webdav_creation',
      'webdav url' => 'field_is_webdav_url',
      'wiki' => 'field_is_wiki',
      'groups audience' => 'og_group_ref',
    ];
  }

}
