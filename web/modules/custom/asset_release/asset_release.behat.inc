<?php

/**
 * @file
 * Contains Drupal\asset_release\asset_release.behat.inc.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\FileTrait;
use Drupal\joinup\Traits\RandomGeneratorTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Behat step definitions for testing asset_releases.
 */
class AssetReleaseSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use FileTrait;
  use RandomGeneratorTrait;
  use RdfEntityTrait;

  /**
   * Test asset releases.
   *
   * @var \Drupal\rdf_entity\Entity\Rdf[]
   */
  protected $assetReleases = [];

  /**
   * Navigates to the canonical page display of a asset_release.
   *
   * @param string $asset_release
   *   The name of the asset_release.
   *
   * @When (I )go to (the homepage of )the :asset_release asset release
   * @When (I )visit (the homepage of )the :asset_release asset release
   */
  public function visitCollection($asset_release) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $asset_release */
    $asset_release = $this->getAssetReleaseByName($asset_release);
    $this->visitPath($asset_release->url());
  }

  /**
   * Creates a number of asset_releases with data provided in a table.
   *
   * Table format:
   * @codingStandardsIgnoreStart
   * title             | documentation | release number | release notes | creation date    | modification date |
   * Foo asset_release | text.pdf      | 1              | Notes 1       | 28-01-1995 12:05 |                   |
   * Bar asset_release | text.pdf      | 2.3            | Notes 2       | 28-01-1995 12:06 |                   |
   * @codingStandardsIgnoreEnd
   *
   * Fields title, release number and release notes are required.
   *
   * @param TableNode $asset_release_table
   *   The asset_release data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )asset releases:
   */
  public function givenAssetReleases(TableNode $asset_release_table) {
    $aliases = self::assetReleaseFieldAliases();

    foreach ($asset_release_table->getColumnsHash() as $asset_release) {
      $values = [];
      // Replace the column aliases with the actual field names.
      foreach ($asset_release as $key => $value) {
        if (array_key_exists($key, $aliases)) {
          $values[$aliases[$key]] = $value;
        }
        else {
          throw new \Exception("Unknown column '$key' in asset_release table.");
        }
      };

      $values = $this->convertValueAliases($values);

      $this->createAssetRelease($values);
    }
  }

  /**
   * Creates a asset_release with data provided in a table.
   *
   * Table format:
   * | title             | Sample asset_release                    |
   * | documentation     | text.pdf                                |
   * | is version of     | Solution                                |
   * | release number    | 1                                       |
   * | release notes     | Notes on the release                    |
   * | keywords          | key1, key2                              |
   * ...
   *
   * Fields title, release number and release notes required.
   *
   * @param TableNode $asset_release_table
   *   The asset_release data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )asset release:
   */
  public function givenAssetRelease(TableNode $asset_release_table) {
    $aliases = self::assetReleaseFieldAliases();

    $values = [];
    // Replace the column aliases with the actual field names.
    foreach ($asset_release_table->getRowsHash() as $key => $value) {
      if (array_key_exists($key, $aliases)) {
        $values[$aliases[$key]] = $value;
      }
      else {
        throw new \Exception("Unknown column '$key' in asset_release table.");
      }
    };

    $values = $this->convertValueAliases($values);

    $this->createAssetRelease($values);
  }

  /**
   * Creates a asset release from the given property and field data.
   *
   * @param array $values
   *   An optional associative array of values, keyed by property name.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   A new asset release entity.
   *
   * @throws \Exception
   *   Thrown when a given image is not found.
   */
  protected function createAssetRelease(array $values) {
    if (!empty($values['field_isr_documentation'])) {
      $values['field_isr_documentation'] = [$this->createFile($values['field_isr_documentation'], $this->getMinkParameter('files_path'))];
    }

    $values['rid'] = 'asset_release';
    $values['id'] = isset($values['id']) ? $values['id'] : $this->getRandomUri();
    $asset_release = Rdf::create($values);
    $asset_release->save();
    $this->assetReleases[$asset_release->id()] = $asset_release;

    return $asset_release;
  }

  /**
   * Deletes a asset release.
   *
   * @param string $asset_release
   *   The name of the asset release.
   *
   * @When (I )delete the :asset_release asset release
   */
  public function deleteAssetRelease($asset_release) {
    /** @var \Drupal\rdf_entity\RdfInterface $asset_release */
    $this->getAssetReleaseByName($asset_release)->delete();
  }

  /**
   * Returns the asset release with the given name.
   *
   * If multiple asset releases have the same name,
   * the first one will be returned.
   *
   * @param string $title
   *   The asset release name.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The asset release.
   */
  protected function getAssetReleaseByName($title) {
    return $this->getRdfEntityByLabel($title, 'asset_release');
  }

  /**
   * Checks the number of available asset releases.
   *
   * @param int $number
   *   The expected number of asset releases.
   *
   * @throws \Exception
   *   Throws an exception when the expected number
   *   does not match the one found.
   *
   * @Then I should have :number asset release(s)
   */
  public function assertAssetReleaseCount($number) {
    $actual = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', 'asset_release')
      ->exists('field_isr_is_version_of')
      ->count()
      ->execute();
    if ($actual != $number) {
      throw new \Exception("Wrong number of asset release entities. Expected number: $number, actual number: $actual.");
    }
  }

  /**
   * Remove any created asset releases.
   *
   * @AfterScenario
   */
  public function cleanAssetReleases() {
    // Remove any asset_releases that were created.
    foreach ($this->assetReleases as $asset_release) {
      $asset_release->delete();
    }
  }

  /**
   * Field alias mapping.
   *
   * @return array
   *    Mapping.
   */
  protected static function assetReleaseFieldAliases() {
    // Mapping alias - field name.
    return [
      'uri' => 'id',
      'title' => 'label',
      'contact information' => 'field_isr_contact_information',
      'creation date' => 'field_isr_creation_date',
      'description' => 'field_isr_description',
      'distribution' => 'field_isr_distribution',
      'documentation' => 'field_isr_documentation',
      'included asset' => 'field_isr_included_asset',
      'is version of' => 'field_isr_is_version_of',
      'keywords' => 'field_keywords',
      'language' => 'field_isr_language',
      'modification date' => 'field_isr_modification_date',
      'owner' => 'field_isr_owner',
      'release number' => 'field_isr_release_number',
      'release notes' => 'field_isr_release_notes',
      'related solutions' => 'field_isr_related_solutions',
      'solution type' => 'field_isr_solution_type',
      'spacial coverage' => 'field_spacial_coverage',
      'status' => 'field_status',
      'topic' => 'field_topic',
      'translation' => 'field_isr_translation',
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
    // Check for the OG group.
    if (isset($fields['field_isr_distribution'])) {
      $distribution = $this->getRdfEntityByLabel($fields['field_isr_distribution'], 'asset_distribution');
      $fields['field_isr_distribution'] = [$distribution->id()];
    }

    // Conver the name of the is version of field to an id.
    if (isset($fields['field_isr_is_version_of'])) {
      $collection = $this->getRdfEntityByLabel($fields['field_isr_is_version_of'], 'solution');
      $fields['field_isr_is_version_of'] = $collection->id();
    }

    return $fields;
  }

}
