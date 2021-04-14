<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_export\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Tests the RDF export functionality.
 *
 * @group rdf_entity
 */
class RdfExportTest extends BrowserTestBase {

  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'rdf_export',
    'rdf_taxonomy',
    'sparql_entity_serializer_test',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Testing entity.
   *
   * @var \Drupal\rdf_entity\RdfInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->setUpSparql();
    parent::setUp();

    $this->entity = Rdf::create([
      'rid' => 'fruit',
      'id' => 'http://example.com/apple',
      'label' => 'Apple',
    ]);
    $this->entity->save();
  }

  /**
   * Tests the RDF export functionality.
   */
  public function testRdfExport() {
    $fixture_dir = __DIR__ . '/../../fixtures/content-negotiation/rdf_entity';
    $this->drupalLogin($this->drupalCreateUser(['export rdf metadata']));

    $this->drupalGet($this->entity->toUrl('rdf-export'));
    $page = $this->getSession()->getPage();
    $page->clickLink('Turtle Terse RDF Triple Language');
    $this->assertSession()->statusCodeEquals(200);
    $actual_content = trim($page->getContent());
    $expected_content = trim(file_get_contents("$fixture_dir/turtle"));
    $this->assertEquals($expected_content, $actual_content);

    $this->drupalGet($this->entity->toUrl('rdf-export'));
    $page = $this->getSession()->getPage();
    $page->clickLink('RDF/XML');
    $this->assertSession()->statusCodeEquals(200);
    $actual_content = trim($page->getContent());
    $expected_content = trim(file_get_contents("$fixture_dir/rdfxml"));
    $this->assertEquals($expected_content, $actual_content);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->entity->delete();
    parent::tearDown();
  }

}
