<?php

namespace Drupal\Tests\joinup_federation\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\Entity\RdfEntityGraph;

/**
 * Tests the whitelist builder from the 'user_selection_filter' step plugin.
 *
 * @coversDefaultClass \Drupal\joinup_federation\Plugin\pipeline\Step\UserSelectionFilter
 *
 * @group joinup_federation
 */
class UserSelectionFilterWhitelistBuilderTest extends StepTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'rdf_entity_provenance',
    'rdf_schema_field_validation',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create the 'default' and 'staging' graphs.
    $graph = Yaml::decode(file_get_contents(__DIR__ . '/../../../../../../profiles/joinup/config/install/rdf_entity.graph.default.yml'));
    RdfEntityGraph::create($graph)->save();
    $graph = Yaml::decode(file_get_contents(__DIR__ . '/../../../config/install/rdf_entity.graph.staging.yml'));
    RdfEntityGraph::create($graph)->save();

    // All testing bundle and field definitions are from the module config.
    $this->installConfig(['joinup_federation_test']);

    // Create testing entities.
    foreach (static::getEntityData() as $id => $values) {
      $values += ['id' => $id, 'graph' => 'staging'];
      Rdf::create($values)->save();
    }
  }

  /**
   * @covers ::buildWhitelist
   */
  public function test() {
    /** @var \Drupal\pipeline\Plugin\PipelineStepPluginManager $manager */
    $manager = $this->container->get('plugin.manager.pipeline_step');
    /** @var \Drupal\joinup_federation_test\Plugin\pipeline\Step\TestUserSelectionFilter $step_plugin */
    $step_plugin = $manager->createInstance('test_user_selection_filter');

    $step_plugin->buildWhitelistWrapper('solution', [
      // Solution allowed for federation. http://solution/2 is considered
      // blacklisted for the purpose of this test.
      'http://solution/1',
    ]);

    $whitelist = $step_plugin->getWhitelist();

    // Check that http://solution/1 is added to the whitelist.
    $this->assertContains('http://solution/1', $whitelist);
    // Check that http://solution/2 is not added to the whitelist.
    $this->assertNotContains('http://solution/2', $whitelist);
    // An entity referred by a whitelisted and a blacklisted solution is added.
    $this->assertContains('http://type1/1', $whitelist);
    // Nested: http://solution/1 > http://type1/1 > http://type2/1. The last one
    // is whitelisted because all upstream path is whitelisted.
    $this->assertContains('http://type2/1', $whitelist);
    // Referred only by a blacklisted solution.
    $this->assertNotContains('http://type1/2', $whitelist);
    // Nested: http://solution/2 > http://type1/2 > http://type2/2. The last one
    // is blacklisted because all upstream path is blacklisted.
    $this->assertNotContains('http://type2/2', $whitelist);
    // Whitelisted because:
    // is blacklisted on: http://solution/2 > http://type1/2 > http://type2/3,
    // but whitelisted on: http://solution/1 > http://type1/1 > http://type2/3.
    $this->assertContains('http://type2/3', $whitelist);
  }

  /**
   * Returns data for creating testing entities.
   *
   * @return array[]
   *   Entity values.
   */
  protected static function getEntityData() {
    return [
      // This solution was checked to be federated.
      'http://solution/1' => [
        'rid' => 'solution',
        'reference_to_type1' => [
          // This is referred by the blacklisted solution but is whitelisted
          // because is referred also by this whitelisted solution.
          'http://type1/1',
        ],
        'reference_to_solution' => [
          'http://solution/2',
        ],
      ],
      // This solution is blacklisted. Even is referred by 'http://solution/1'
      // will not be added to the whitelist.
      'http://solution/2' => [
        'rid' => 'solution',
        'reference_to_type1' => [
          'http://type1/1',
          // Not whitelisted as is referred only by a blacklisted solution.
          'http://type1/2',
        ],
      ],
      'http://type1/1' => [
        'rid' => 'type1',
        'reference_to_type2' => [
          // Nested reference.
          'http://type2/1',
          'http://type2/3',
        ],
      ],
      'http://type1/2' => [
        'rid' => 'type1',
        'reference_to_type2' => [
          'http://type2/2',
          // On the path http://solution/2 > http://type1/2 > http://type2/3 is
          // blacklisted but is also referred by a whitelisted.
          'http://type2/3',
        ],
      ],
      'http://type2/1' => [
        'rid' => 'type2',
        'reference_to_solution' => [
          // Circular, but it doesn't hurt.
          'http://solution/1',
        ],
      ],
      'http://type2/2' => [
        'rid' => 'type2',
      ],
      'http://type2/3' => [
        'rid' => 'type2',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    /** @var \Drupal\rdf_entity\RdfEntitySparqlStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('rdf_entity');
    $storage->delete($storage->loadMultiple(array_keys(static::getEntityData())));
    parent::tearDown();
  }

}
