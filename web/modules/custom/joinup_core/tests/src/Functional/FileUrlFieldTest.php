<?php

namespace Drupal\Tests\joinup_core\Functional;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\file\FileInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\Tests\joinup_core\Traits\FileUrlTrait;
use Drupal\Tests\rdf_entity\Functional\RdfWebTestBase;
use Drupal\file_url\FileUrlHandler;
use Drupal\Tests\rdf_entity\Traits\EntityUtilityTrait;

/**
 * Provides methods specifically for testing File module's field handling.
 *
 * @group joinup_core
 */
class FileUrlFieldTest extends RdfWebTestBase {

  use FileUrlTrait;
  use EntityUtilityTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file_url_entity_test',
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
   * Tests upload of a file to an file URL field.
   */
  public function testSingleValuedWidgetLocalFile() {
    $this->drupalLogin($this->rootUser);

    $field_name = 'field_file_url';
    $test_file = $this->getTestFile('text');
    $this->assertTrue($test_file instanceof FileInterface);

    // Test file for new entities.
    $this->drupalGet('rdf_entity/add/file_url');
    $this->addFileUrlItem($field_name, 'upload', $test_file->getFileUri());
    $this->drupalPostForm(NULL, ['label[0][value]' => 'Foo'], 'Save');

    // Check that the file has been uploaded to the file URL field.
    $rdf_entity = $this->loadEntityByLabel('rdf_entity', 'Foo');
    $rdf_entity_file = FileUrlHandler::urlToFile($rdf_entity->{$field_name}->target_id);
    $this->assertFileExists($rdf_entity_file->getFileUri());

    // Ensure the file can be downloaded.
    $this->drupalGet(file_create_url($rdf_entity_file->getFileUri()));
    $this->assertSession()->statusCodeEquals(200);

    // Edit the entity and change the field to a remote URL.
    $this->drupalPostForm($rdf_entity->toUrl('edit-form'), [], 'Remove');
    $url = 'http://example.com/' . $this->randomMachineName();
    $this->addFileUrlItem($field_name, 'remote', $url);
    $this->drupalPostForm(NULL, [], 'Save');

    // @todo We should not need cache clearing hre. The cache should have been
    //   be wiped out at this point. Fix this regression in ISAICP-3392.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3392
    \Drupal::entityTypeManager()->getStorage('rdf_entity')->resetCache([$rdf_entity->id()]);

    // Check that the remote URL replaced the uploaded file.
    $rdf_entity = Rdf::load($rdf_entity->id());
    $this->assertEquals($url, $rdf_entity->{$field_name}->target_id);
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
  protected function addFileUrlItem($field_name, $file_mode, $value) {
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
