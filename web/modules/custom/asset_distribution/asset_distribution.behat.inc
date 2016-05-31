<?php

/**
 * @file
 * Contains \AssetDistributionSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\FileTrait;
use Drupal\joinup\Traits\RandomGeneratorTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Behat step definitions for testing asset distributions.
 */
class AssetDistributionSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use FileTrait;
  use RandomGeneratorTrait;
  use RdfEntityTrait;

  /**
   * Test asset distributions.
   *
   * @var \Drupal\rdf_entity\Entity\Rdf[]
   */
  protected $assetDistributions = [];

  /**
   * Navigates to the canonical page display of an asset distribution.
   *
   * @param string $asset_distribution
   *   The title of the asset distribution.
   *
   * @When (I )go to (the homepage of )the :asset_distribution distribution
   * @When (I )visit (the homepage of )the :asset_distribution distribution
   */
  public function visitDistribution($asset_distribution) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $asset_distribution */
    $asset_distribution = $this->getAssetDistributionByName($asset_distribution);
    $this->visitPath($asset_distribution->url());
  }

  /**
   * Navigates to the edit form of an asset distribution.
   *
   * @param string $asset_distribution
   *   The title of the $asset_distribution.
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
   *   The asset distribution data.
   *
   * @codingStandardsIgnoreStart
   *   Table format:
   *   uri                                     | title            | description                | access url                               | file     | creation date     | modification date
   *   http://joinup.eu/asset_distribution/foo | Foo distribution | This is a foo distribution | http://base_url.com/path-to-file.zip     | test.zip | 28-01-1995 12:05  |
   *   http://joinup.eu/asset_distribution/bar | Bar distribution | This is a bar distribution | http://external_url.com/path-to-file.zip | test.zip | 28-01-1995 12:06  |
   * @codingStandardsIgnoreEnd
   *
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
   * Creates an asset distribution from the given property and field data.
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
  protected function createAssetDistribution(array $values) {
    // Add image.
    if (!empty($values['field_ad_file'])) {
      $values['field_ad_file'] = [$this->createFile($values['field_ad_file'], $this->getMinkParameter('files_path'))];
    }

    $values['rid'] = 'asset_distribution';
    $values['id'] = isset($values['id']) ? $values['id'] : $this->getRandomUri();
    $asset_distribution = Rdf::create($values);
    $asset_distribution->save();
    $this->assetDistributions[$asset_distribution->id()] = $asset_distribution;

    return $asset_distribution;
  }

  /**
   * Deletes an asset distribution.
   *
   * @param string $asset_distribution
   *   The title of the asset distribution.
   *
   * @When (I )delete the :asset_distribution asset distribution
   */
  public function deleteAssetDistribution($asset_distribution) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $asset_distribution */
    $this->getAssetDistributionByName($asset_distribution)->delete();
  }

  /**
   * Returns the asset distribution with the given title.
   *
   * If multiple asset distributions have the same title,
   * the first one will be returned.
   *
   * @param string $title
   *   The asset distribution title.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   The asset distribution.
   *
   * @throws \InvalidArgumentException
   *   Thrown when an asset distribution with the given title does not exist.
   */
  protected function getAssetDistributionByName($title) {
    return $this->getRdfEntityByLabel($title, 'asset_distribution');
  }

  /**
   * Returns the Solution with the given title.
   *
   * If multiple solution have the same title, the first one will be returned.
   *
   * @param string $title
   *   The solution title.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   The solution.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a solution with the given title does not exist.
   */
  protected function getSolutionByName($title) {
    return $this->getRdfEntityByLabel($title, 'solution');
  }

  /**
   * Checks the number of available asset distributions.
   *
   * @param int $number
   *   The expected number of asset distributions.
   *
   * @Then I should have :number asset distribution(s)
   */
  public function assertAssetDistributionCount($number) {
    $this->assertRdfEntityCount($number, 'asset_distribution');
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
  }

  /**
   * Field alias mapping.
   *
   * @return array
   *    Mapping.
   */
  protected static function assetDistributionFieldAliases() {
    // Mapping alias - field name.
    return [
      'uri' => 'id',
      'title' => 'label',
      'access url' => 'field_ad_access_url',
      'creation date' => 'field_ad_creation_date',
      'description' => 'field_ad_description',
      'file' => 'field_ad_file',
      'file size' => 'field_ad_file_size',
      'format' => 'field_ad_format',
      'licence' => 'field_ad_licence',
      'modification date' => 'field_ad_modification_date',
      'representation technique' => 'field_ad_repr_technique',
      'status' => 'field_status',
    ];
  }

}
