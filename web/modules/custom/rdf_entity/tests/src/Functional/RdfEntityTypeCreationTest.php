<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_entity\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Tests the creation of RdfEntityType entities (RDF bundles)
 *
 * @group rdf_entity
 */
class RdfEntityTypeCreationTest extends BrowserTestBase {

  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'rdf_entity',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->setUpSparql();
    parent::setUp();
  }

  /**
   * Tests that we can create and edit RDF entity types.
   */
  public function testRdfTypeCreation(): void {
    $account = $this->drupalCreateUser(['administer site configuration'], 'rdf_admin', TRUE);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/structure/rdf_type/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals('Add rdf type | Drupal');

    $edit = [
      'name' => 'Test',
      'rid' => 'test',
      'sparql_entity_storage[rdf_type]' => 'sparql_entity_storage[rdf_type]',
      'sparql_entity_storage[graph][default]' => 'http://example.com/graph/event',
      'sparql_entity_storage[base_fields_mapping][rid][target_id][predicate]' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
      'sparql_entity_storage[base_fields_mapping][rid][target_id][format]' => 'resource',
      'sparql_entity_storage[base_fields_mapping][label][value][predicate]' => 'https://schema.org/name',
      'sparql_entity_storage[base_fields_mapping][label][value][format]' => 'resource',
    ];

    $this->drupalPostForm(NULL, $edit, 'op');
    $this->assertSession()->linkByHrefExists('/admin/structure/rdf_type/manage/test');
    $this->drupalGet('/admin/structure/rdf_type/manage/test');
    foreach ($edit as $name => $value) {
      $this->assertSession()->fieldValueEquals($name, $value);
    }
  }

}
