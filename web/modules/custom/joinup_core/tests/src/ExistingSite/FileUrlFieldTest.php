<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\ExistingSite;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\joinup_core\Traits\FileUrlTrait;
use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\Tests\rdf_entity\Traits\DrupalTestTraits\RdfEntityCreationTrait;
use Drupal\Tests\rdf_entity\Traits\EntityUtilityTrait;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Drupal\file\FileInterface;
use Drupal\file_url\FileUrlHandler;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\sparql_entity_storage\UriEncoder;
use weitzman\LoginTrait\LoginTrait;

/**
 * Provides methods specifically for testing File module's field handling.
 *
 * @group joinup_core
 */
class FileUrlFieldTest extends JoinupExistingSiteTestBase {

  use EntityUtilityTrait;
  use FileUrlTrait;
  use LoginTrait;
  use RdfEntityCreationTrait;
  use SparqlConnectionTrait;
  use StringTranslationTrait;

  /**
   * The helper service that deals with files and stream wrappers.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The RDF storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $sparqlStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fileSystem = $this->container->get('file_system');
    // @todo This will no longer be needed once ISAICP-3392 is fixed.
    // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3392
    $this->sparqlStorage = $this->container->get('entity_type.manager')->getStorage('rdf_entity');
  }

  /**
   * Tests upload of a file to an file URL field.
   */
  public function testSingleValuedWidgetLocalFile(): void {
    $account = $this->createUser([], NULL, FALSE, ['roles' => ['moderator']]);
    $this->drupalLogin($account);

    $solution = $this->createRdfEntity([
      'rid' => 'solution',
      'label' => $this->randomString(),
      'field_is_state' => 'validated',
      // Ensure a parent collection.
      'collection' => $this->createRdfEntity([
        'rid' => 'collection',
        'label' => $this->randomString(),
        'field_ar_state' => 'validated',
      ]),
    ]);
    $licence = $this->createRdfEntity([
      'rid' => 'licence',
      'label' => $this->randomString(),
    ]);

    $test_file = $this->getTestFile('text');
    $this->assertTrue($test_file instanceof FileInterface);

    $distribution = $this->createRdfEntity([
      'rid' => 'asset_distribution',
      'label' => $this->randomString(),
      'og_audience' => $solution->id(),
      'field_ad_licence' => $licence->id(),
      'field_ad_description' => $this->randomString(),
    ]);

    $field_name = 'field_ad_access_url';

    // Test file for new entities.
    $url = "/rdf_entity/" . UriEncoder::encodeUrl($distribution->id()) . "/edit";

    $this->drupalGet($url);
    $this->addFileUrlItem($field_name, 'upload', $test_file->getFileUri());
    $this->drupalPostForm(NULL, ['label[0][value]' => 'Foo'], 'Save');

    // Check that the file has been uploaded to the file URL field.
    $distribution = Rdf::load($distribution->id());
    $rdf_entity_file = FileUrlHandler::urlToFile($distribution->{$field_name}->target_id);
    $this->markEntityForCleanup($rdf_entity_file);
    $initial_uri = $rdf_entity_file->getFileUri();
    $this->assertFileExists($initial_uri);

    // Ensure the file can be downloaded.
    $this->drupalGet(file_create_url($initial_uri));
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet($distribution->toUrl());
    $this->getSession()->getPage()->clickLink('Edit');
    // Upload the same file again to test if the file is saved in a new location
    // while still keeping the same file basename.
    $this->drupalPostForm(NULL, [], 'Remove');
    $this->addFileUrlItem($field_name, 'upload', $test_file->getFileUri());
    $this->drupalPostForm(NULL, [], 'Save');

    // @todo We should not need cache clearing here. The cache should have been
    //   wiped out at this point. Fix this regression in ISAICP-3392.
    // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3392
    $this->sparqlStorage->resetCache([$distribution->id()]);

    // Check that the file has been uploaded to the file URL field.
    $distribution = Rdf::load($distribution->id());
    $rdf_entity_file = FileUrlHandler::urlToFile($distribution->{$field_name}->target_id);
    $second_uri = $rdf_entity_file->getFileUri();
    $this->assertFileExists($second_uri);

    // Ensure the file can be downloaded.
    $this->drupalGet(file_create_url($second_uri));
    $this->assertSession()->statusCodeEquals(200);

    // Check that the same file is uploaded to different locations.
    $this->assertNotEquals($initial_uri, $second_uri);

    // Check that the basename is preserved.
    $this->assertEquals($this->fileSystem->basename($initial_uri), $this->fileSystem->basename($second_uri));

    // Edit the entity and change the field to a remote URL.
    $this->drupalGet($distribution->toUrl());
    $this->getSession()->getPage()->clickLink('Edit');
    $this->drupalPostForm(NULL, [], 'Remove');
    $url = 'http://example.com/' . $this->randomMachineName();
    $this->addFileUrlItem($field_name, 'remote', $url);
    $this->drupalPostForm(NULL, [], 'Save');

    // @todo We should not need cache clearing here. The cache should have been
    //   wiped out at this point. Fix this regression in ISAICP-3392.
    // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3392
    $this->sparqlStorage->resetCache([$distribution->id()]);

    // Check that the remote URL replaced the uploaded file.
    $distribution = Rdf::load($distribution->id());
    $this->assertEquals($url, $distribution->{$field_name}->target_id);
  }

  /**
   * Appends a new item to a file URL field.
   *
   * @param string $field_name
   *   The file URL field name.
   * @param string $file_mode
   *   The radio button option value with the file mode ('upload', 'remote').
   * @param string $value
   *   Either a file URI of the local file being uploaded (when $file_mode
   *   equals 'upload') or the remote URL (when $file_mode equals 'remote').
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   If the field doesn't exist.
   */
  protected function addFileUrlItem($field_name, $file_mode, $value): void {
    $session = $this->getSession();
    $page = $session->getPage();

    $field_name_html = str_replace('_', '-', $field_name);

    // Narrow the search to the field's wrapper.
    $wrapper = $page->find('xpath', "//div[@data-drupal-selector='edit-{$field_name_html}-wrapper']");
    if (!$wrapper) {
      throw new ElementNotFoundException($session, $field_name);
    }

    /** @var \Behat\Mink\Element\NodeElement $radio */
    $radio = $wrapper->find('xpath', "//input[@type='radio']");
    if (!$radio) {
      throw new ElementNotFoundException($session, $field_name);
    }

    // Select the file mode.
    $radio->setValue($file_mode);

    if ($file_mode === 'upload') {
      $file_system = $this->container->get('file_system');
      $wrapper->attachFileToField('Choose a file', $file_system->realpath($value));
    }
    elseif ($file_mode === 'remote') {
      $wrapper->fillField('Remote URL', $value);
    }
  }

}
