<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\Kernel;

use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_entity\RdfInterface;
use Drupal\Tests\rdf_entity\Traits\RdfDatabaseConnectionTrait;
use PHPUnit\Framework\Assert;

/**
 * Tests for the Joinup relation manager service.
 *
 * @group joinup_core
 * @coversDefaultClass \Drupal\joinup_core\JoinupRelationManager
 */
class JoinupRelationManagerTest extends KernelTestBase {

  use RdfDatabaseConnectionTrait;
  use RdfEntityTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'allowed_formats',
    'asset_release',
    'cached_computed_field',
    'collection',
    'comment',
    'datetime',
    'ds',
    'facets',
    'field',
    'field_group',
    'file',
    'file_url',
    'filter',
    'image',
    'inline_entity_form',
    'joinup_core',
    'link',
    'node',
    'og',
    'options',
    'piwik_reporting_api',
    'rdf_draft',
    'rdf_entity',
    'rdf_taxonomy',
    'search_api',
    'search_api_field',
    'smart_trim',
    'solution',
    'state_machine',
    'system',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * The Joinup relation manager service. This is the system under test.
   *
   * @var \Drupal\joinup_core\JoinupRelationManagerInterface
   */
  protected $joinupRelationManager;

  /**
   * A collection of test RDF entities.
   *
   * @var \Drupal\rdf_entity\RdfInterface[][]
   */
  protected $testRdfEntities = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpSparql();

    $this->installEntitySchema('rdf_entity');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('file');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    $this->installConfig([
      'rdf_entity',
      'rdf_draft',
      'joinup_core',
      'asset_release',
      'collection',
      'solution',
    ]);
    $this->installSchema('file', ['file_usage']);

    $this->joinupRelationManager = $this->container->get('joinup_core.relations_manager');

    // Create two test collections and solutions.
    for ($i = 0; $i < 2; $i++) {
      foreach (['collection', 'solution'] as $bundle_id) {
        $state_field_name = $bundle_id === 'collection' ? 'field_ar_state' : 'field_is_state';
        $this->testRdfEntities[$bundle_id][$i] = $this->createRdfEntity($bundle_id, [
          $state_field_name => 'draft',
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // Clean up the test entities that were created in the setup.
    foreach ($this->testRdfEntities as $bundle_id => $entities) {
      foreach ($entities as $entity) {
        $entity->delete();
      }
    }

    parent::tearDown();
  }

  /**
   * @covers ::getCollectionIds
   */
  public function testGetCollectionIds(): void {
    $this->assertRdfEntityIds('collection', $this->joinupRelationManager->getCollectionIds());
  }

  /**
   * @covers ::getSolutionIds
   */
  public function testGetSolutionIds(): void {
    $this->assertRdfEntityIds('solution', $this->joinupRelationManager->getSolutionIds());
  }

  /**
   * Checks that the given RDF entity IDs match those defined in the setup.
   *
   * @param string $bundle_id
   *   The RDF entity bundle of the entities to check.
   * @param array $ids
   *   The entity IDs retrieved from the database.
   */
  protected function assertRdfEntityIds(string $bundle_id, array $ids): void {
    $expected_ids = array_map(function (RdfInterface $entity): string {
      return $entity->id();
    }, $this->testRdfEntities[$bundle_id]);

    sort($ids);
    sort($expected_ids);
    Assert::assertEquals($expected_ids, $ids);
  }

}
