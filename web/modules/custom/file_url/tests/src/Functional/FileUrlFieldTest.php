<?php

namespace Drupal\Tests\file_url;

use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\Tests\rdf_entity\Functional\RdfWebTestBase;
use Drupal\file_url\Entity\RemoteFile;
use Drupal\file_url\FileUrlHandler;
use Drupal\Tests\file_url\Traits\FileUrlTrait;

/**
 * Provides methods specifically for testing File module's field handling.
 */
class FileUrlFieldTest extends RdfWebTestBase {

  use FileUrlTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'rdf_entity',
    'file_url',
    'file',
    'file_url_entity_test',
    'field_ui',
  ];

  /**
   * An array of graphs to clear after the test.
   *
   * @var array
   */
  protected $usedGraphs = [
    'http://example.com/file_url/draft',
    'http://example.com/file_url/published',
  ];

  /**
   * An user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The rdf storage.
   *
   * @var \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage
   */
  protected $rdfStorage;

  /**
   * The file system helper service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->rdfStorage = \Drupal::getContainer()->get('entity_type.manager')->getStorage('rdf_entity');
    $this->fileSystem = \Drupal::getContainer()->get('file_system');
    $this->adminUser = $this->drupalCreateUser([
      'administer rdf entity',
      'view rdf entity',
      'edit file_url rdf entity',
      'edit own file_url rdf entity',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests upload of a file to an file URL field.
   */
  public function testSingleValuedWidgetLocalFile() {
    $this->rdfStorage = $this->container->get('entity.manager')->getStorage('rdf_entity');
    $type_name = 'file_url';
    $field_name = 'field_file_url';
    $settings = ['rid' => $type_name];
    $test_file = $this->getTestFile('text');
    $this->assertTrue($test_file instanceof FileInterface, "Test file created.");

    // Test file for new entities.
    $id = $this->uploadFileUrl($test_file, $field_name, NULL, $settings);
    $this->rdfStorage->resetCache(array($id));
    $rdf_entity = $this->rdfStorage->load($id);
    $rdf_entity_file = FileUrlHandler::urlToFile($rdf_entity->{$field_name}->target_id);
    $this->assertTrue(is_file($rdf_entity_file->getFileUri()), 'New file saved to disk on rdf_entity creation.');

    // Test when the entity has a remote file and we upload a local one.
    // The widget should quietly overwrite the remote one as the select input
    // now points to a file and not a remote file.
    $rdf_entity = $this->createRdfEntity($settings + [
      $field_name => $this->getFileAbsoluteUri($rdf_entity_file),
    ]);
    $id = $this->uploadFileUrl($test_file, $field_name, $rdf_entity->id());
    $this->rdfStorage->resetCache(array($id));
    $rdf_entity = $this->rdfStorage->load($id);
    $rdf_entity_file = FileUrlHandler::urlToFile($rdf_entity->{$field_name}->target_id);
    $this->assertTrue(is_file($rdf_entity_file->getFileUri()), 'New file saved to disk on rdf_entity creation.');

    // Ensure the file can be downloaded.
    $this->drupalGet(file_create_url($rdf_entity_file->getFileUri()));
    $this->assertResponse(200, 'Confirmed that the generated URL is correct by downloading the shipped file.');
  }

  /**
   * Tests setting a remote file for an file URL field.
   */
  public function testSingleValuedWidgetRemoteFile() {
    $this->rdfStorage = $this->container->get('entity.manager')->getStorage('rdf_entity');
    $type_name = 'file_url';
    $field_name = 'field_file_url';
    $settings = ['rid' => $type_name];
    $test_file = $this->getTestFile('text');
    $this->assertTrue($test_file instanceof FileInterface, "Test file created.");

    $id = $this->setRemoteFile($this->getFileAbsoluteUri($test_file), $field_name, NULL, $settings);
    $this->rdfStorage->resetCache(array($id));
    $rdf_entity = $this->rdfStorage->load($id);
    $rdf_entity_file = FileUrlHandler::urlToFile($rdf_entity->{$field_name}->target_id);
    $this->assertTrue($rdf_entity_file instanceof RemoteFile, 'The remote file entity was saved successfully.');

    // Ensure the file can be downloaded.
    $this->drupalGet($this->getFileAbsoluteUri($rdf_entity_file));
    $this->assertResponse(200, 'Confirmed that the generated URL is correct by downloading the shipped file.');

    // Test when the entity has a local file and we set a remote one.
    // The widget should quietly overwrite the remote one as the select input
    // now points to a remote file.
    $rdf_entity = $this->createRdfEntity($settings + [
      $field_name => $test_file,
    ]);

    $id = $this->setRemoteFile($this->getFileAbsoluteUri($test_file), $field_name, $rdf_entity->id());
    $this->rdfStorage->resetCache(array($id));
    $rdf_entity = $this->rdfStorage->load($id);
    $rdf_entity_file = FileUrlHandler::urlToFile($rdf_entity->{$field_name}->target_id);
    $this->assertTrue($rdf_entity_file instanceof RemoteFile, 'The remote file entity was saved successfully.');

    // Ensure the file can be downloaded.
    $this->drupalGet($this->getFileAbsoluteUri($rdf_entity_file));
    $this->assertResponse(200, 'Confirmed that the generated URL is correct by downloading the shipped file.');
  }

  /**
   * Uploads a file to an file URL field of an rdf entity.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file to be uploaded.
   * @param string $field_name
   *   The name of the field on which the files should be saved.
   * @param string|null $id
   *   The rdf entity id or empty if a new entity should bre created.
   * @param array $extras
   *   Additional values when a new rdf entity is created.
   *
   * @return int
   *   The rdf entity id.
   */
  public function uploadFileUrl(FileInterface $file, $field_name, $id, array $extras = []) {
    $rdf_entity = $this->getRdfEntity($id, $extras);
    $select = 'file';
    $file_uri = $this->fileSystem->realpath($file->getFileUri());
    $field_html_name = 'files[' . $field_name . "_0_file-wrap_file]";

    return $this->prepareAndPostForm($rdf_entity->id(), $field_name, $select, $file_uri, $field_html_name);
  }

  /**
   * Sets a remote to an file URL field of an rdf entity.
   *
   * @param string $uri
   *   The absolute path of the remote file.
   * @param string $field_name
   *   The name of the field on which the files should be saved.
   * @param string|null $id
   *   The rdf entity id or empty if a new entity should bre created.
   * @param array $extras
   *   Additional values when a new rdf entity is created.
   *
   * @return int
   *   The rdf entity id.
   */
  public function setRemoteFile($uri, $field_name, $id, array $extras = []) {
    $rdf_entity = $this->getRdfEntity($id, $extras);
    $select = 'remote-file';
    $field_html_name = "{$field_name}[0][file-wrap][remote-file]";

    return $this->prepareAndPostForm($rdf_entity->id(), $field_name, $select, $uri, $field_html_name);
  }

  /**
   * Prepares the values on a form and posts it.
   *
   * @param string $rdf_entity_id
   *   The rdf entity uri.
   * @param string $field_name
   *   The machine name of the field.
   * @param string $select
   *   The machine name of the select option for the file URL field. Available
   *   options are 'file' and 'remote-file'.
   * @param string $file_uri
   *   The file uri.
   * @param string $field_html_name
   *   The html name selector for the field to fill.
   *
   * @return string
   *   The id of the rdf entity.
   */
  protected function prepareAndPostForm($rdf_entity_id, $field_name, $select, $file_uri, $field_html_name) {
    // Set that the file is local.
    $edit["{$field_name}[0][file-wrap][select]"] = $select;
    $edit[$field_html_name] = $file_uri;

    $edit_url = Url::fromRoute('entity.rdf_entity.edit_form', ['rdf_entity' => $rdf_entity_id]);
    $internal_path = $edit_url->getInternalPath();
    $this->drupalPostForm($internal_path, $edit, t('Save'));

    return $rdf_entity_id;
  }

  /**
   * Gets the absolute URI of an entity.
   *
   * @param \Drupal\file\FileInterface $file
   *   The entity for which to generate the URI.
   *
   * @return string
   *   The absolute URI.
   */
  protected function getFileAbsoluteUri(FileInterface $file) {
    return file_create_url($file->getFileUri());
  }

  /**
   * Loads or creates a new rdf entity and returns it.
   *
   * @param string|null $id
   *   The id of the rdf entity. If null, a new entity will be created.
   * @param array $extras
   *   Optional values to pass if an entity is created.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The loaded or created entity.
   */
  protected function getRdfEntity($id, array $extras = []) {
    $this->rdfStorage = \Drupal::getContainer()->get('entity_type.manager')->getStorage('rdf_entity');
    if (!empty($id)) {
      $this->rdfStorage->resetCache([$id]);
      $rdf_entity = $this->rdfStorage->load($id);
    }
    else {
      // Save at least one revision to better simulate a real site.
      $rdf_entity = $this->createRdfEntity($extras);
      $id = $rdf_entity->id();
      $this->rdfStorage->resetCache([$id]);
      $rdf_entity = $this->rdfStorage->load($id);
      $this->assertTrue($rdf_entity instanceof RdfInterface, 'Rdf entity saved.');
    }

    return $rdf_entity;
  }

}
