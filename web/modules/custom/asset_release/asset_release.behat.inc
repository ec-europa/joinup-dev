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
   * Test asset_releases.
   *
   * @var \Drupal\rdf_entity\Entity\Rdf[]
   */
  protected $asset_releases = [];

  /**
   * Navigates to the add asset_release form.
   *
   * @When (I )go to the add asset_release form
   * @When (I )visit the add asset_release form
   */
  public function visitAddAssetReleaseForm() {
    $this->visitPath('/rdf_entity/add/asset_release');
  }

  /**
   * Navigates to the canonical page display of a asset_release.
   *
   * @param string $asset_release
   *   The name of the asset_release.
   *
   * @When (I )go to (the homepage of )the :asset_release asset_release
   * @When (I )visit (the homepage of )the :asset_release asset_release
   */
  public function visitCollection($asset_release) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $asset_release */
    $asset_release = $this->getAssetReleaseByName($asset_release);
    $this->visitPath($asset_release->url());
  }

  /**
   * Navigates to the edit form of a asset_release.
   *
   * @param string $asset_release
   *   The name of the asset_release.
   *
   * @When (I )go to the :asset_release asset_release edit form
   * @When (I )visit the :asset_release asset_release edit form
   */
  public function visitEditAssetRelease($asset_release) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $asset_release */
    $asset_release = $this->getAssetReleaseByName($asset_release);
    $path = $asset_release->url('edit-form');
    $this->visitPath($path);
  }

  /**
   * Creates a number of asset_releases with data provided in a table.
   *
   * Table format:
   * @codingStandardsIgnoreStart
   * title        | description            | document | closed | creation date    | elibrary creation | moderation | modification date | landing page               | webdav creation | webdav url                  | wiki
   * Foo asset_release | This is a foo asset_release | text.pdf | yes    | 28-01-1995 12:05 | no                | yes        |                   | http://foo-url-example.com | yes             | http://joinup.eu/foo/webdav | http://foo-wiki-example.com
   * Bar asset_release | This is a bar asset_release | text.pdf | no     | 28-01-1995 12:06 | yes               | no         |                   | http://bar-url-example.com | no              |                             | http://bar-wiki-example.com
   * @codingStandardsIgnoreEnd
   *
   * Fields title, description and elibrary creation are mandatory.
   *
   * @param TableNode $asset_release_table
   *   The asset_release data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )asset_releases:
   */
  public function givenAssetReleases(TableNode $asset_release_table) {
    $aliases = self::asset_releaseFieldAliases();

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
   * | title             | Sample asset_release                         |
   * | description       | A sample asset_release                       |
   * | document          | text.pdf                                |
   * | moderation        | no|yes                                  |
   * | closed            | no|yes                                  |
   * | elibrary creation | facilitators|members|registered users   |
   * | landing page      | http://example.com/foobar/landing       |
   * | webdav creation   | no|yes                                  |
   * | webdav url        | http://joinup.eu/asset_release/foobar/webdav |
   * | wiki              | http://example.wiki/foobar/wiki         |
   *
   * Fields title, description and elibrary creation are required.
   *
   * @param TableNode $asset_release_table
   *   The asset_release data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )asset_release:
   */
  public function givenAssetRelease(TableNode $asset_release_table) {
    $aliases = self::asset_releaseFieldAliases();

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
   * Creates a asset_release from the given property and field data.
   *
   * @param array $values
   *   An optional associative array of values, keyed by property name.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   A new asset_release entity.
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
    $this->asset_releases[$asset_release->id()] = $asset_release;

    return $asset_release;
  }

  /**
   * Deletes a asset_release.
   *
   * @param string $asset_release
   *   The name of the asset_release.
   *
   * @When (I )delete the :asset_release asset_release
   */
  public function deleteAssetRelease($asset_release) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $asset_release */
    $this->getAssetReleaseByName($asset_release)->delete();
  }

  /**
   * Returns the AssetRelease with the given name.
   *
   * If multiple asset_release have the same name, the first one will be returned.
   *
   * @param string $title
   *   The asset_release name.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   The asset_release.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a asset_release with the given name does not exist.
   */
  protected function getAssetReleaseByName($title) {
    return $this->getRdfEntityByLabel($title, 'asset_release');
  }

  /**
   * Returns the release with the given name.
   *
   * If multiple asset_release have the same name, the first one will be returned.
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
      ->condition('rid', 'asset_release')
      ->exists('field_isr_is_version_of')
      ->condition('label', $title)
      ->range(0, 1);
    $result = $query->execute();

    if (empty($result)) {
      throw new \InvalidArgumentException("The release with the name '$title' was not found.");
    }

    return Rdf::load(reset($result));
  }

  /**
   * Checks the number of available asset_releases.
   *
   * @param int $number
   *   The expected number of asset_releases.
   *
   * @throws \Exception
   *   Throws an exception when the expected number is not equal to the given.
   *
   * @Then I should have :number asset_release(s)
   */
  public function assertAssetReleaseCount($number) {
    // We cannot rely on $this->assertRdfEntityCount() because a asset_release shares
    // the same bundle with a release. We need to distinguish them by the
    // presence of the 'is_version_of' field - if this field is missing it is a
    // asset_release, if it is present it is a release.
    $actual = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', 'asset_release')
      ->notExists('field_isr_is_version_of')
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
    // the same bundle with a asset_release. We need to distinguish them by the
    // presence of the 'is_version_of' field - if this field is missing it is a
    // asset_release, if it is present it is a release.
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
   * Remove any created asset_releases.
   *
   * @AfterScenario
   */
  public function cleanAssetReleases() {
    // Remove any asset_releases that were created.
    foreach ($this->asset_releases as $asset_release) {
      $asset_release->delete();
    }
  }

  /**
   * Field alias mapping.
   *
   * @return array
   *    Mapping.
   */
  protected static function asset_releaseFieldAliases() {
    // Mapping alias - field name.
    return [
      'uri' => 'id',
      'title' => 'label',
      'affiliations requests' => 'field_isr_affiliations_requests',
      'contact information' => 'field_isr_contact_information',
      'creation date' => 'field_isr_creation_date',
      'description' => 'field_isr_description',
      'distribution' => 'field_isr_distribution',
      'documentation' => 'field_isr_documentation',
      'elibrary creation' => 'field_isr_elibrary_creation',
      'included asset' => 'field_isr_included_asset',
      'issue tracker' => 'field_isr_issue_tracker',
      'keywords' => 'field_isr_keywords',
      'landing page' => 'field_isr_landing_page',
      'language' => 'field_isr_language',
      'latest release' => 'field_isr_latest_release',
      'metrics page' => 'field_isr_metrics_page',
      'moderation' => 'field_isr_moderation',
      'modification date' => 'field_isr_modification_date',
      'owner' => 'field_isr_owner',
      'related asset_releases' => 'field_isr_related_asset_releases',
      'asset_release category' => 'field_isr_asset_release_category',
      'asset_release type' => 'field_isr_asset_release_type',
      'source code repository' => 'field_isr_source_code_repository',
      'spacial coverage' => 'field_isr_spacial_coverage',
      'status' => 'field_isr_status',
      'topic' => 'field_isr_topic',
      'translation' => 'field_isr_translation',
      'webdav creation' => 'field_isr_webdav_creation',
      'webdav url' => 'field_isr_webdav_url',
      'wiki' => 'field_isr_wiki',
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
      'field_isr_moderation' => ['no' => 0, 'yes' => 1],
      'field_isr_elibrary_creation' =>
        ['facilitators' => 0, 'members' => 1, 'registered users' => 2],
      'field_isr_webdav_creation' => ['no' => 0, 'yes' => 1],
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
    if (isset($fields['field_isr_distribution'])) {
      $distribution = $this->getRdfEntityByLabel($fields['field_isr_distribution'], 'asset_distribution');
      $fields['field_isr_distribution'] = [$distribution->id()];
    }

    // Check for the OG group.
    if (isset($fields['og_group_ref'])) {
      $collection = $this->getRdfEntityByLabel($fields['og_group_ref'], 'collection');
      $fields['og_group_ref'] = $collection->id();
    }

    return $fields;
  }

}
