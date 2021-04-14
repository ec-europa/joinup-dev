<?php

declare(strict_types = 1);

namespace Drupal\Tests\sparql_entity_storage\Kernel;

use Drupal\sparql_test\Entity\SparqlTest;

/**
 * Tests a field with multiple columns.
 *
 * @group sparql_entity_storage
 */
class MultiColumnFieldTest extends SparqlKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'link',
    'sparql_multi_column_field_test',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['sparql_multi_column_field_test']);
  }

  /**
   * Tests the link field.
   */
  public function testLinkField(): void {
    SparqlTest::create([
      'type' => 'fruit',
      'id' => 'http://example.com/apple',
      'label' => $this->randomString(),
      'link' => [
        'uri' => 'http://example.com',
        'title' => 'My link title',
      ],
    ])->save();

    // Ensures that saving a link field with 2 columns mapped will save and load
    // both columns in the same delta as expected.
    $entity = SparqlTest::load('http://example.com/apple');
    $this->assertEquals('http://example.com', $entity->get('link')->uri);
    $this->assertEquals('My link title', $entity->get('link')->title);
  }

}
