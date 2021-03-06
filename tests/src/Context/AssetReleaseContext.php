<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\asset_release\Entity\AssetReleaseInterface;
use Drupal\joinup\Traits\EntityReferenceTrait;
use Drupal\joinup\Traits\FileTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\SearchTrait;

/**
 * Behat step definitions for testing asset_releases.
 */
class AssetReleaseContext extends RawDrupalContext {

  use EntityReferenceTrait;
  use FileTrait;
  use SearchTrait;
  use RdfEntityTrait;

  /**
   * Test releases.
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
   * @When I go to (the homepage of )the :asset_release release
   * @When I visit (the homepage of )the :asset_release release
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function visitRelease(string $asset_release): void {
    /** @var \Drupal\rdf_entity\Entity\Rdf $entity */
    $entity = $this->getAssetReleaseByName($asset_release);
    $this->visitPath($entity->toUrl()->toString());
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
   * @param \Behat\Gherkin\Node\TableNode $asset_release_table
   *   The asset_release data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )releases:
   */
  public function givenAssetReleases(TableNode $asset_release_table): void {
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
      }

      $values = $this->convertValueAliases($values);

      $this->createAssetRelease($values);
    }
  }

  /**
   * Creates a asset_release with data provided in a table.
   *
   * Table format:
   * | title             | Sample asset_release |
   * | documentation     | text.pdf             |
   * | is version of     | Solution             |
   * | release number    | 1                    |
   * | release notes     | Notes on the release |
   * | keywords          | key1, key2           |
   * ...
   *
   * Fields title, release number and release notes required.
   *
   * @param \Behat\Gherkin\Node\TableNode $asset_release_table
   *   The asset_release data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )release:
   */
  public function givenAssetRelease(TableNode $asset_release_table): void {
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
    }

    $values = $this->convertValueAliases($values);

    $this->createAssetRelease($values);
  }

  /**
   * Creates a asset release from the given property and field data.
   *
   * @param array $values
   *   An optional associative array of values, keyed by property name.
   *
   * @return \Drupal\asset_release\Entity\AssetReleaseInterface
   *   A new asset release entity.
   *
   * @throws \Exception
   *   Thrown when a given image is not found.
   */
  protected function createAssetRelease(array $values): AssetReleaseInterface {
    if (!empty($values['field_isr_documentation'])) {
      foreach ($values['field_isr_documentation'] as &$filename) {
        $filename = [$this->createFile($filename)->id()];
      }
    }

    $asset_release = $this->createRdfEntity('asset_release', $values);
    $this->assetReleases[$asset_release->id()] = $asset_release;

    return $asset_release;
  }

  /**
   * Deletes a asset release.
   *
   * @param string $asset_release
   *   The name of the asset release.
   *
   * @When I delete the :asset_release release
   */
  public function deleteAssetRelease(string $asset_release): void {
    $asset_release = $this->getAssetReleaseByName($asset_release);
    if (isset($this->assetReleases[$asset_release->id()])) {
      unset($this->assetReleases[$asset_release->id()]);
    }
    $asset_release->skip_notification = TRUE;
    $asset_release->delete();
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
   * @return \Drupal\asset_release\Entity\AssetReleaseInterface
   *   The asset release.
   */
  protected function getAssetReleaseByName(string $title): AssetReleaseInterface {
    return $this->getRdfEntityByLabel($title, 'asset_release');
  }

  /**
   * Checks the number of available releases.
   *
   * @param int $number
   *   The expected number of releases.
   *
   * @throws \Exception
   *   Throws an exception when the expected number
   *   does not match the one found.
   *
   * @Then I should have :number release(s)
   */
  public function assertReleaseCount(int $number): void {
    $this->assertRdfEntityCount($number, 'asset_release');
  }

  /**
   * Remove any created asset releases.
   *
   * @AfterScenario
   */
  public function cleanAssetReleases(): void {
    if (empty($this->assetReleases)) {
      return;
    }

    // Since we might be cleaning up many releases, temporarily disable the
    // feature to commit the index after every query.
    $this->disableCommitOnUpdate();

    // Remove any asset_releases that were created.
    foreach ($this->assetReleases as $asset_release) {
      $asset_release->skip_notification = TRUE;
      $asset_release->delete();
    }
    $this->assetReleases = [];
    $this->enableCommitOnUpdate();
  }

  /**
   * Field alias mapping.
   *
   * @return array
   *   Mapping.
   */
  protected static function assetReleaseFieldAliases(): array {
    // Mapping alias - field name.
    return [
      'uri' => 'id',
      'title' => 'label',
      'contact information' => 'field_isr_contact_information',
      'creation date' => 'created',
      'description' => 'field_isr_description',
      'documentation' => 'field_isr_documentation',
      'included asset' => 'field_isr_included_asset',
      'is version of' => 'field_isr_is_version_of',
      'keywords' => 'field_keywords',
      'language' => 'field_isr_language',
      'modification date' => 'changed',
      'owner' => 'field_isr_owner',
      'release number' => 'field_isr_release_number',
      'release notes' => 'field_isr_release_notes',
      'related solutions' => 'field_isr_related_solutions',
      'solution type' => 'field_isr_solution_type',
      'spatial coverage' => 'field_spatial_coverage',
      'status' => 'field_status',
      'translation' => 'field_isr_translation',
      'state' => 'field_isr_state',
    ];
  }

  /**
   * Converts values from user friendly to normal machine values.
   *
   * @param array $fields
   *   An array of fields keyed by field name.
   *
   * @return array
   *   The array with the values converted.
   *
   * @throws \Exception
   *    Throws an exception when a mapped value is not found.
   */
  protected function convertValueAliases(array $fields): array {
    // Convert the name of the is version of field to an id.
    // This is handled separately as it is not an entity reference but an
    // og_standard_reference.
    if (isset($fields['field_isr_is_version_of'])) {
      $solution = $this->getRdfEntityByLabel($fields['field_isr_is_version_of'], 'solution');
      $fields['field_isr_is_version_of'] = $solution->id();
    }

    // Convert any entity reference field label value with the entity id.
    $fields = $this->convertEntityReferencesValues('rdf_entity', 'asset_release', $this->parseRdfEntityFields($fields));

    return $fields;
  }

  /**
   * Asserts that releases are shown in the page in the exact order.
   *
   * This step is meant for the releases overview page within a solution.
   *
   * @param \Behat\Gherkin\Node\TableNode $asset_release_table
   *   The asset release titles.
   *
   * @throws \Exception
   *    Thrown when a wrong number of releases is found or the releases are not
   *    ordered properly.
   *
   * @Given I should see the (following )releases in the exact order:
   */
  public function assertReleasesAndOrder(TableNode $asset_release_table): void {
    $releases = [];

    // Replace the column aliases with the actual field names.
    foreach ($asset_release_table->getColumnsHash() as $title) {
      $releases[] = $title['release'];
    }

    $release_titles = $this->getSession()->getPage()->findAll('css', '.timeline__release > .timeline__release-content > .timeline__meta > h2.timeline__release-title');
    if (count($release_titles) != count($releases)) {
      throw new \Exception("Wrong number of releases found.");
    }

    foreach ($releases as $index => $title) {
      if ($release_titles[$index]->getText() !== $title) {
        throw new \Exception("Title {$releases[$index]} is not in the correct order.");
      }
    }
  }

  /**
   * Checks that a release is marked as the latest one.
   *
   * This step is meant for the releases overview page within a solution.
   *
   * @param string $release
   *   The name of the release.
   *
   * @throws \Exception
   *    Thrown if the release is not marked as latest in the page or not found.
   *
   * @Then the :release release should be marked as the latest release
   */
  public function assertReleaseIsLatest(string $release): void {
    $release = $this->getAssetReleaseByName($release);

    $latest_releases = $this->getSession()->getPage()->findAll('css', '.is-latest');
    if (empty($latest_releases)) {
      throw new \Exception('No release marked as latest was found in the page.');
    }

    if (count($latest_releases) > 1) {
      throw new \Exception('Multiple releases marked as latest were found in the page.');
    }

    $release_version = $release->getVersion();
    $name_with_version = $release->label() . " " . $release_version;

    $latest_release = reset($latest_releases);
    if ($latest_release->find('css', '.timeline__release-title')->getText() !== $name_with_version) {
      throw new \Exception("{$release->label()} is not marked as the latest release.");
    }
  }

}
