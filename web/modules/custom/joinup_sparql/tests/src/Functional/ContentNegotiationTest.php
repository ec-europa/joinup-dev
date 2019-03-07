<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_sparql\Functional;

use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_taxonomy\Entity\RdfTerm;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\rdf_entity\Traits\RdfDatabaseConnectionTrait;
use EasyRdf\Format;

/**
 * Tests RDF entities content negotiation.
 *
 * @group joinup
 */
class ContentNegotiationTest extends BrowserTestBase {

  use RdfDatabaseConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'joinup_sparql',
    'rdf_taxonomy',
    'sparql_entity_serializer_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->setUpSparql();
    parent::setUp();
  }

  /**
   * Tests content negotiation.
   */
  public function testContentNegotiation(): void {
    $term = RdfTerm::create([
      'vid' => 'fruit_type',
      'tid' => 'http://example.com/citrus-fruits',
      'name' => 'Citrus fruits',
    ]);
    $term->save();
    $term_url = $term->toUrl();

    $entity = Rdf::create([
      'rid' => 'fruit',
      'id' => 'http://example.com/apple',
      'label' => 'Apple',
    ]);
    $entity->save();
    $entity_url = $entity->toUrl();

    $this->drupalLogin($this->createUser(['view rdf entity']));

    $fixtures_dir = drupal_get_path('module', 'rdf_entity') . '/tests/fixtures/content-negotiation/rdf_entity';
    $fixtures = file_scan_directory($fixtures_dir, '/.*/');

    foreach ($fixtures as $path => $fixture) {
      /** @var \EasyRdf\Format $format */
      $format = Format::getFormats()[$fixture->name];
      $mime_type = $format->getDefaultMimeType();
      $term_path = dirname($path) . '/../taxonomy_term/' . $fixture->name;
      $expected_rdf_entity_body = trim(file_get_contents(DRUPAL_ROOT . '/' . $path));
      $expected_taxonomy_term_body = trim(file_get_contents(DRUPAL_ROOT . '/' . $term_path));

      // An unambiguous RDF mime type as 'Accept' header.
      // Example: application/rdf+xml.
      $this->drupalGet($entity_url, [], ['Accept' => $mime_type]);
      $session = $this->getSession();
      $this->assertEquals($expected_rdf_entity_body, $this->getBody());

      $this->drupalGet($term_url, [], ['Accept' => $mime_type]);
      $this->assertEquals($expected_taxonomy_term_body, $this->getBody());

      // Ambiguous 'Accept' header where the RDF mime type wins.
      // Example: text/html;q=0.9,application/rdf+xml,*/*;q=0.8.
      $accept = "text/html;q=0.9,application/xml;q=0.9,$mime_type,*/*;q=0.8";
      $this->drupalGet($entity_url, [], ['Accept' => $accept]);
      $this->assertEquals($expected_rdf_entity_body, $this->getBody());

      $this->drupalGet($term_url, [], ['Accept' => $accept]);
      $this->assertEquals($expected_taxonomy_term_body, $this->getBody());

      // Ambiguous 'Accept' header where the 'text/html' mime type wins.
      // Example: text/html,application/xml;q=0.9,application/rdf+xml,*/*;q=0.8.
      $accept = "text/html,application/xml;q=0.9,$mime_type,*/*;q=0.8";
      $this->drupalGet($entity_url, [], ['Accept' => $accept]);
      $this->assertStringStartsWith('text/html', $session->getResponseHeader('Content-Type'));
      $this->assertStringStartsWith('<!DOCTYPE html>', $this->getBody());

      $this->drupalGet($term_url, [], ['Accept' => $accept]);
      $this->assertStringStartsWith('text/html', $session->getResponseHeader('Content-Type'));
      $this->assertStringStartsWith('<!DOCTYPE html>', $this->getBody());
    }

    // Ambiguous 'Accept' header where 'text/html' wins.
    // Example: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8.
    $accept = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8';
    $this->drupalGet($entity_url, [], ['Accept' => $accept]);
    $this->assertStringStartsWith('text/html', $session->getResponseHeader('Content-Type'));
    $this->assertStringStartsWith('<!DOCTYPE html>', $this->getBody());

    $this->drupalGet($term_url, [], ['Accept' => $accept]);
    $this->assertStringStartsWith('text/html', $session->getResponseHeader('Content-Type'));
    $this->assertStringStartsWith('<!DOCTYPE html>', $this->getBody());

    // Test a non-entity page.
    $this->drupalGet('<front>', [], ['Accept' => 'application/rdf+xml']);
    $this->assertEquals(406, $session->getStatusCode());
    $this->assertEquals('Not acceptable format: rdfxml', $this->getBody());
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    Rdf::load('http://example.com/apple')->delete();
    RdfTerm::load('http://example.com/citrus-fruits')->delete();
    parent::tearDown();
  }

  /**
   * Returns the body of the current response.
   *
   * @return string
   *   The body part of the last HTTP response,
   */
  protected function getBody() {
    return trim($this->getSession()->getPage()->getContent());
  }

}
