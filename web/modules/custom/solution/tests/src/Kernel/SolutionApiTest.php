<?php

namespace Drupal\Tests\solution\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\Entity\RdfEntityGraph;
use Drupal\rdf_entity\Entity\RdfEntityMapping;
use Drupal\rdf_entity\Entity\RdfEntityType;
use Drupal\Tests\rdf_entity\Traits\RdfDatabaseConnectionTrait;

/**
 * Tests solution API.
 *
 * @group solution
 */
class SolutionApiTest extends KernelTestBase {

  use RdfDatabaseConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'rdf_entity',
    'search_api',
    'solution',
    'state_machine',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpSparql();
  }

  /**
   * Tests that the parent collection is correctly set.
   */
  public function testSetParentCollection() {
    $this->installEntitySchema('user');

    $draft_graph = Yaml::decode(file_get_contents(__DIR__ . '/../../../../../../profiles/joinup/config/install/rdf_entity.graph.draft.yml'));
    RdfEntityGraph::create($draft_graph)->save();
    $default_graph = Yaml::decode(file_get_contents(__DIR__ . '/../../../../../../profiles/joinup/config/install/rdf_entity.graph.default.yml'));
    RdfEntityGraph::create($default_graph)->save();

    // We don't install the original 'collection' RDF entity bundle definition
    // and fields because we want to avoid installing the whole list of
    // dependencies. Thus we create only a simplified version of collection
    // bundle for the purpose of this test.
    RdfEntityType::create([
      'rid' => 'collection',
      'name' => 'Collection Type Mock',
    ])->save();
    // Create the 'collection' corresponding mapping config entity.
    $mapping_values = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/rdf_entity.mapping.rdf_entity.collection.yml'));
    RdfEntityMapping::create($mapping_values)->save();
    // Create the collection affiliation field.
    $field_storage_config = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/field.storage.rdf_entity.field_ar_affiliates.yml'));
    $field_config = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/field.field.rdf_entity.collection.field_ar_affiliates.yml'));
    FieldStorageConfig::create($field_storage_config)->save();
    FieldConfig::create($field_config)->save();

    // Create the 'solution' corresponding mapping config entity and install
    // some configs for the purpose of this test. We do this to avoid installing
    // the whole 'solution' module config in order to avoid a lot of
    // dependencies.
    $bundle_config = Yaml::decode(file_get_contents(__DIR__ . '/../../../config/install/rdf_entity.rdfentity.solution.yml'));
    RdfEntityType::create($bundle_config)->save();
    $mapping_values = Yaml::decode(file_get_contents(__DIR__ . '/../../../config/install/rdf_entity.mapping.rdf_entity.solution.yml'));
    RdfEntityMapping::create($mapping_values)->save();
    $field_storage_config = Yaml::decode(file_get_contents(__DIR__ . '/../../../config/install/field.storage.rdf_entity.field_is_state.yml'));
    $field_config = Yaml::decode(file_get_contents(__DIR__ . '/../../../config/install/field.field.rdf_entity.solution.field_is_state.yml'));
    FieldStorageConfig::create($field_storage_config)->save();
    FieldConfig::create($field_config)->save();

    // Create two collections.
    Rdf::create([
      'rid' => 'collection',
      'id' => 'http://example.com/collection/1',
      'label' => $this->randomString(),
    ])->save();
    Rdf::create([
      'rid' => 'collection',
      'id' => 'http://example.com/collection/2',
      'label' => $this->randomString(),
    ])->save();
    // Create a solution in both collections.
    Rdf::create([
      'rid' => 'solution',
      'id' => 'http://example.com/solution',
      'label' => $this->randomString(),
      'collections' => [
        'http://example.com/collection/1',
        'http://example.com/collection/2',
      ],
      'field_is_state' => 'validated',
    ])->save();

    // Check that each collection has the solution as affiliate.
    $this->assertEquals('http://example.com/solution', Rdf::load('http://example.com/collection/1')->field_ar_affiliates->target_id);
    $this->assertEquals('http://example.com/solution', Rdf::load('http://example.com/collection/2')->field_ar_affiliates->target_id);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    /** @var \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('rdf_entity');
    $storage->delete(Rdf::loadMultiple([
      'http://example.com/solution',
      'http://example.com/collection/1',
      'http://example.com/collection/2',
    ]));
    parent::tearDown();
  }

}
