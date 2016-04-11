<?php

/**
 * @file
 * Contains \AssetDistributionSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\file\Entity\File;
use Drupal\rdf_entity\Entity\Query\Sparql\SparqlArg;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Behat step definitions for testing asset distributions.
 */
class AssetDistributionSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  /**
   * Test asset distributions.
   *
   * @var \Drupal\rdf_entity\Entity\Rdf[]
   */
  protected $assetDistributions = [];

  /**
   * Test files.
   *
   * @var \Drupal\file\Entity\File[]
   */
  protected $files = [];

  /**
   * Navigates to the canonical page display of a asset distribution.
   *
   * @param string $asset_distribution
   *   The name of the asset distribution.
   *
   * @When (I )go to (the homepage of )the :asset_distribution distribution
   * @When (I )visit (the homepage of )the :asset_distribution distribution
   */
  public function visitCollection($asset_distribution) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $distribution */
    $asset_distribution = $this->getAssetDistributionByName($asset_distribution);
    $this->visitPath($asset_distribution->url());
  }

  /**
   * Navigates to the edit form of a asset distribution.
   *
   * @param string $asset_distribution
   *   The name of the $asset_distribution.
   *
   * @When (I )go to the :asset_distribution asset distribution edit form
   * @When (I )visit the :asset_distribution asset distribution edit form
   */
  public function visitEditAssetDistribution($asset_distribution) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $asset_distribution */
    $asset_distribution = $this->getAssetDistributionByName($asset_distribution);
    $path = $asset_distribution->url('edit-form');
    $this->visitPath($path);
  }

  /**
   * Creates a number of asset distributions with data provided in a table.
   *
   * @param TableNode $asset_distribution_table
   *   The solution data.
   *
   * @codingStandardsIgnoreStart
   *   Table format:
   *   Table format:
   * uri                                      | name              | description                  | access url                               | file     | creation date     | modification date
   * http://joinup.eu/asset_distribution/foo  | Foo distribution  | This is a foo distribution   | http://base_url.com/path-to-file.zip     | test.zip | 28-01-1995 12:05  |
   * http://joinup.eu/asset_distribution/bar  | Bar distribution  | This is a bar distribution   | http://external_url.com/path-to-file.zip | test.zip | 28-01-1995 12:06  |
   * @codingStandardsIgnoreEnd
   * Fields title and description are mandatory.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )asset distributions:
   */
  public function givenAssetDistributions(TableNode $asset_distribution_table) {
    $aliases = self::assetDistributionFieldAliases();

    foreach ($asset_distribution_table->getColumnsHash() as $asset_distribution) {
      $values = [];
      // Replace the column aliases with the actual field names.
      foreach ($asset_distribution as $key => $value) {
        if (array_key_exists($key, $aliases)) {
          $values[$aliases[$key]] = $value;
        }
        else {
          throw new \Exception("Unknown column '$key' in asset distribution table.");
        }
      };
      $this->createAssetDistribution($values);
    }
  }

  /**
   * Field alias mapping.
   *
   * @return array
   *    Mapping.
   */
  private static function assetDistributionFieldAliases() {
    // Mapping alias - field name.
    return [
      'uri' => 'id',
      'name' => 'label',
      'access url' => 'field_ad_access_url',
      'creation date' => 'field_ad_creation_date',
      'description' => 'field_ad_description',
      'file' => 'field_ad_file',
      'file size' => 'field_ad_file_size',
      'format' => 'field_ad_format',
      'licence' => 'field_ad_licence',
      'modification date' => 'field_ad_modification_date',
      'representation technique' => 'field_ad_repr_technique',
      'status' => 'field_ad_status',
    ];
  }

  /**
   * Creates an asset distribution with data provided in a table.
   *
   * Table format:
   * | title             | Sample distribution                          |
   * | uri               | http://joinup.eu/distribution/foobar         |
   * | description       | A sample distribution                        |
   * | access url        | http://internal-or-external.path/to/file.zip |
   * | file              | test.zip                                     |
   *
   * Fields title and description are required.
   *
   * @param TableNode $asset_distribution_table
   *   The asset distribution data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )asset distribution:
   */
  public function givenAssetDistribution(TableNode $asset_distribution_table) {
    $aliases = self::assetDistributionFieldAliases();

    $values = [];
    // Replace the column aliases with the actual field names.
    foreach ($asset_distribution_table->getRowsHash() as $key => $value) {
      if (array_key_exists($key, $aliases)) {
        $values[$aliases[$key]] = $value;
      }
      else {
        throw new \Exception("Unknown column '$key' in solution table.");
      }
    };
    $this->createAssetDistribution($values);
  }

  /**
   * Creates a asset distribution from the given property and field data.
   *
   * @param array $values
   *   An optional associative array of values, keyed by property name.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   A new asset distribution entity.
   *
   * @throws \Exception
   *   Thrown when a given file is not found.
   */
  public function createAssetDistribution(array $values) {
    // Add image.
    if (!empty($values['field_ad_file'])) {
      $filename = $values['field_ad_file'];
      $files_path = $this->getMinkParameter('files_path');
      $path = rtrim(realpath($files_path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
      if (!is_file($path)) {
        throw new \Exception("File '$filename' was not found in file path '$files_path'.");
      }
      // Copy the file into the public files folder and turn it into a File
      // entity before linking it to the collection.
      $uri = 'public://' . $filename;
      $destination = file_unmanaged_copy($path, $uri);
      $file = File::create(['uri' => $destination]);
      $file->save();
      $values['field_ad_file'] = [$file->id()];

      $this->files[$file->id()] = $file;
    }

    $values['rid'] = 'asset_distribution';
    $asset_distribution = Rdf::create($values);
    $asset_distribution->save();
    $this->assetDistributions[$asset_distribution->id()] = $asset_distribution;

    return $asset_distribution;
  }

  /**
   * Deletes an asset distribution.
   *
   * @param string $asset_distribution
   *   The name of the asset distribution.
   *
   * @When (I )delete the :asset_distribution asset distribution
   */
  public function deleteAssetDistribution($asset_distribution) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $asset_distribution */
    $this->getAssetDistributionByName($asset_distribution)->delete();
  }

  /**
   * Returns the asset distribution with the given name.
   *
   * If multiple asset distributions have the same name,
   * the first one will be returned.
   *
   * @param string $name
   *   The asset distribution name.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   The asset distribution.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a asset distribution with the given name does not exist.
   */
  protected function getAssetDistributionByName($name) {
    $query = \Drupal::entityQuery('rdf_entity')
      ->condition('?entity', 'rdf:type', SparqlArg::uri('http://www.w3.org/ns/adms#AssetDistribution'))
      ->condition('?entity', SparqlArg::uri('http://purl.org/dc/terms/title'), SparqlArg::literal($name))
      ->range(0, 1);
    $result = $query->execute();

    if (empty($result)) {
      throw new \InvalidArgumentException("The asset distribution with the name '$name' was not found.");
    }

    return Rdf::load(reset($result));
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
  public function getSolutionByName($name) {
    $query = \Drupal::entityQuery('rdf_entity')
      ->condition('?entity', 'rdf:type', SparqlArg::uri('http://www.w3.org/ns/adms#Asset'))
      ->condition('?entity', SparqlArg::uri('http://purl.org/dc/terms/title'), SparqlArg::literal($name))
      ->range(0, 1);
    $result = $query->execute();

    if (empty($result)) {
      throw new \InvalidArgumentException("The solution with the name '$name' was not found.");
    }

    // We need to skip the static cache and load the unchanged entity from the
    // database.
    return \Drupal::entityTypeManager()
      ->getStorage('rdf_entity')
      ->loadUnchanged(reset($result));
  }

  /**
   * Checks the number of available asset distributions.
   *
   * @param int $number
   *   The expected number of asset distributions.
   *
   * @throws \Exception
   *   Thrown when the number of asset distributions does not
   *   match the expectation.
   *
   * @Then I should have :number asset distribution(s)
   */
  public function assertAssetDistributionCount($number) {
    $actual = \Drupal::entityQuery('rdf_entity')
      ->condition('?entity', 'rdf:type', '<http://www.w3.org/ns/adms#AssetDistribution>')
      ->count()
      ->execute();
    if ($actual != $number) {
      throw new \Exception("Wrong number of asset distributions. Expected number: $number, actual number: $actual.");
    }
  }

  /**
   * Checks if the given asset distributions belongs to the given solution.
   *
   * If there are multiple solutions or asset distributions with
   * the same name, then only the first one is checked.
   *
   * @param string $asset_distribution
   *   The name of the asset distribution to check.
   * @param string $solution
   *   The title of the custom page to check.
   *
   * @throws \Exception
   *   Thrown when an asset distribution with the given title doesn't exist.
   *
   * @Then the :asset_distribution asset distribution is related to the :solution solution
   */
  public function assertAssetRelationship($asset_distribution, $solution) {
    /** @var \Drupal\rdf_entity\RdfInterface $asset_distribution */
    $asset_distribution = $this->getAssetDistributionByName($asset_distribution);

    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    $this->clearStaticCaches();
    $solution = $this->getSolutionByName($solution);

    // This handles the solutions added by form submit.
    if (!in_array($asset_distribution, $this->assetDistributions)) {
      $this->assetDistributions[$asset_distribution->id()] = $asset_distribution;
    }

    foreach ($solution->get('field_is_distribution') as $distribution) {
      if ($distribution->getValue()['target_id'] == $asset_distribution->id()) {
        // Test passes.
        return;
      }
    }

    throw new \Exception("The solution '{$solution->label()}' is not associated with asset distribution '{$asset_distribution->label()}'.");
  }

  /**
   * Remove any created asset distributions.
   *
   * @AfterScenario
   */
  public function cleanAssetDistributions() {
    // Remove any asset distributions that were created.
    foreach ($this->assetDistributions as $asset_distribution) {
      $asset_distribution->delete();
    }
    // Also remove the image entities that were attached to the collections.
    foreach ($this->files as $file) {
      $file->delete();
    }
  }

}
