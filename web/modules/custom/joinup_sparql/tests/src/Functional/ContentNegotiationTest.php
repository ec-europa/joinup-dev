<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_sparql\Functional;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_taxonomy\Entity\RdfTerm;
use EasyRdf\Format;

/**
 * Tests RDF entities content negotiation.
 *
 * @group joinup
 */
class ContentNegotiationTest extends BrowserTestBase {

  use SparqlConnectionTrait;

  /**
   * Formats to be tested.
   *
   * @var string[]
   */
  const FORMATS = [
    'jsonld',
    'n3',
    'ntriples',
    'rdfxml',
    'turtle',
  ];

  /**
   * Memory cache for expected mime types.
   *
   * @var string[]
   */
  protected $mimeType;

  /**
   * Memory cache for expected response bodies.
   *
   * @var string[][]
   */
  protected $expectedBody;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'joinup_content_negotiation_test',
    'page_cache',
  ];

  /**
   * {@inheritdoc}
   */
  protected function bootEnvironment() {
    parent::bootEnvironment();
    $this->setUpSparql();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['view rdf entity']);
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

    $entity = Rdf::create([
      'rid' => 'fruit',
      'id' => 'http://example.com/apple',
      'label' => 'Apple',
    ]);
    $entity->save();

    foreach (static::FORMATS as $format_name) {
      // An unambiguous RDF mime type as 'Accept' header.
      // Example: application/rdf+xml.
      $mime_type = Format::getFormat($format_name)->getDefaultMimeType();
      $this->assertContent($format_name, $entity, $mime_type);
      $this->assertContent($format_name, $term, $mime_type);

      // Save the entities to test if the cache has been cleared.
      $entity->save();
      $term->save();

      // Ambiguous 'Accept' header where the RDF mime type wins.
      // Example: text/html;q=0.9,application/rdf+xml,*/*;q=0.8.
      $accept = "text/html;q=0.9,application/xml;q=0.9,$mime_type,*/*;q=0.8";
      $this->assertContent($format_name, $entity, $accept);
      $this->assertContent($format_name, $term, $accept);

      // Ambiguous 'Accept' header where the 'text/html' mime type wins.
      // Example: text/html,application/xml;q=0.9,application/rdf+xml,*/*;q=0.8.
      $accept = "text/html,application/xml;q=0.9,$mime_type,*/*;q=0.8";
      $this->assertContent('html', $entity, $accept);
      $this->assertContent('html', $term, $accept);
    }

    // Save the entities to clear the cache.
    $entity->save();
    $term->save();

    // Ambiguous 'Accept' header where 'text/html' wins.
    // Example: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8.
    $accept = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8';
    $this->assertContent('html', $entity, $accept);
    $this->assertContent('html', $term, $accept);

    // Test a non-entity page.
    $this->drupalGet('<front>', [], ['Accept' => 'application/rdf+xml']);
    $this->assertEquals(406, $this->getSession()->getStatusCode());
    $this->assertEquals('Not acceptable format: rdfxml', $this->getBody());
    // Not cached.
    $this->assertNull($this->getSession()->getResponseHeader('X-Drupal-Cache'));
  }

  /**
   * Asserts that content negotiation succeeded.
   *
   * This is a complex/composite assertion that performs a GET request to the
   * $entity canonical page, by sending $accept_header as 'Accept' header value.
   * Three elements are checked twice, once for the non-cached page and twice
   * for the cached version:
   * - If the page has been retrieved from the backend or from cache.
   * - The response 'Content-Type' header.
   * - The response body.
   *
   * @param string $expected_format
   *   The response expected format.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be tested.
   * @param string $accept_header
   *   The request 'Accept' header value.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   If a malformed entity has been passed.
   */
  protected function assertContent(string $expected_format, ContentEntityInterface $entity, string $accept_header): void {
    $this->drupalGet($entity->toUrl(), [], ['Accept' => $accept_header]);
    $session = $this->getSession();

    // Check that the page wasn't cached.
    $this->assertEquals('MISS', $session->getResponseHeader('X-Drupal-Cache'));
    // Check that the response has the correct content type. Usually, the
    // response 'Content-Type' header value is something like:
    // 'text/html; charset=utf-8', so we're testing only the first part.
    $this->assertStringStartsWith($this->getMimeType($expected_format), $session->getResponseHeader('Content-Type'));
    // Check the response body. On 'text/html', we only check the first part.
    if ($expected_format === 'html') {
      $this->assertStringStartsWith('<!DOCTYPE html>', $this->getBody());
    }
    else {
      $this->assertEquals($this->getExpectedBody($expected_format, $entity), $this->getBody());
    }

    // Reload the page to make sure the page has been cached.
    $session->reload();

    // Check that the page was cached.
    $this->assertEquals('HIT', $session->getResponseHeader('X-Drupal-Cache'));
    // Check that we don't get the cache from other format.
    $this->assertStringStartsWith($this->getMimeType($expected_format), $session->getResponseHeader('Content-Type'));
    // Check the response body. On 'text/html', we only check the first part
    // that always is a standard HTML.
    if ($expected_format === 'html') {
      $this->assertStringStartsWith('<!DOCTYPE html>', $this->getBody());
    }
    else {
      $this->assertEquals($this->getExpectedBody($expected_format, $entity), $this->getBody());
    }
  }

  /**
   * Returns the mime type given a format name.
   *
   * @param string $format_name
   *   The format name (e.g. 'html', 'rdfxml', etc).
   *
   * @return string
   *   The mime type.
   */
  protected function getMimeType(string $format_name): string {
    if (!isset($this->mimeType)) {
      $this->mimeType = ['html' => 'text/html'] + array_map(function (Format $format) {
        return $format->getDefaultMimeType();
      }, Format::getFormats());
    }
    return $this->mimeType[$format_name];
  }

  /**
   * Returns the expected response body given an entity and a format name.
   *
   * @param string $format_name
   *   The format name (e.g. 'rdfxml', 'n3', etc).
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The expected response body.
   */
  protected function getExpectedBody(string $format_name, ContentEntityInterface $entity): string {
    if (!isset($this->expectedBody)) {
      $fixtures_dir = __DIR__ . '/../../fixtures/content-negotiation';
      foreach (static::FORMATS as $format) {
        foreach (['rdf_entity', 'taxonomy_term'] as $entity_type_id) {
          $path = realpath("$fixtures_dir/$entity_type_id/$format");
          $this->expectedBody[$entity_type_id][$format] = trim(file_get_contents($path));
        }
      }
    }
    return $this->expectedBody[$entity->getEntityTypeId()][$format_name];
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

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    Rdf::load('http://example.com/apple')->delete();
    RdfTerm::load('http://example.com/citrus-fruits')->delete();
    parent::tearDown();
  }

}
