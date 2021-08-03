<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_document\ExistingSite;

use Drupal\Tests\joinup_core\Traits\TestFileTrait;
use Drupal\Tests\rdf_entity\Traits\DrupalTestTraits\RdfEntityCreationTrait;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Drupal\file\FileInterface;
use Drupal\joinup_document\Entity\DocumentInterface;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Various tests related to document theming.
 */
class DocumentThemeTest extends ExistingSiteBase {

  use RdfEntityCreationTrait;
  use SparqlConnectionTrait;
  use TestFileTrait;

  /**
   * A test file.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * Tests that the logo affects relative fields in the listing tile.
   */
  public function testDocumentLogoListing(): void {
    $community = $this->createRdfEntity([
      'rid' => 'collection',
      'label' => $this->randomString(),
      'field_ar_state' => 'validated',
    ]);

    $document = $this->createNode([
      'type' => 'document',
      'title' => 'Document title',
      'og_audience' => $community->id(),
      'body' => 'Hello document body',
      'field_keywords' => ['keyword_sample'],
      'field_short_title' => 'DocTitle',
    ]);

    $this->assertTrue($document instanceof DocumentInterface);
    $this->assertNotEmpty($document->id());

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $renderer = \Drupal::getContainer()->get('renderer');
    $view_array = $view_builder->view($document, 'view_mode_tile');
    $html = (string) $renderer->renderPlain($view_array);

    $this->assertNotEmpty($html);
    $this->assertStringContainsString('Document title', $html);
    $this->assertStringContainsString('<p>Hello document body</p>', $html);
    $this->assertStringContainsString('keyword_sample', $html);

    $this->file = $this->getTestFile('text');
    $this->file->save();
    $this->assertTrue($this->file instanceof FileInterface);
    $document->set('field_document_logo', $this->file);
    $document->save();

    $view_array = $view_builder->view($document, 'view_mode_tile');
    $html = (string) $renderer->renderPlain($view_array);

    $this->assertNotEmpty($html);
    $this->assertStringContainsString('<div class="listing__image">', $html);
    $this->assertStringContainsString('Document title', $html);
    $this->assertStringNotContainsString('<p>Hello document body</p>', $html);
    $this->assertStringNotContainsString('keyword_sample', $html);
  }

}
