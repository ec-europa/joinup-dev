<?php

namespace Drupal\Tests\joinup_federation\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\sparql_entity_storage\Entity\SparqlGraph;

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
  protected function getUsedStepPlugins(): array {
    return [];
  }

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
    $graph = Yaml::decode(file_get_contents(__DIR__ . '/../../../../../../profiles/joinup/config/install/sparql_entity_storage.graph.default.yml'));
    SparqlGraph::create($graph)->save();
    $graph = Yaml::decode(file_get_contents(__DIR__ . '/../../../config/install/sparql_entity_storage.graph.staging.yml'));
    SparqlGraph::create($graph)->save();

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
    $this->assertContains('http://distro/1', $whitelist);
    // Nested: http://solution/1 > http://version/1 > http://distro/2. The last
    // one is whitelisted because it comes via an whitelisted upstream path.
    $this->assertContains('http://distro/2', $whitelist);
    // Referred only by a blacklisted solution.
    $this->assertNotContains('http://distro/3', $whitelist);
    // Nested: http://solution/2 > http://version/2 > http://distro/4. The last
    // one is blacklisted because the whole upstream path is blacklisted.
    $this->assertNotContains('http://distro/4', $whitelist);
    // Whitelisted because is blacklisted following the path:
    // - http://solution/2 > http://version/2 > http://distro/5,
    // but whitelisted following the path:
    // - http://solution/1 > http://version/1 > http://distro/5.
    $this->assertContains('http://distro/5', $whitelist);
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
        'solution_to_version' => [
          'http://version/1',
        ],
        'solution_to_distro' => [
          // This is referred by the blacklisted solution but is whitelisted
          // because is referred also by this whitelisted solution.
          'http://distro/1',
        ],
        'solution_to_solution' => [
          'http://solution/2',
        ],
      ],
      // This solution is blacklisted. Even is referred by 'http://solution/1'
      // will not be added to the whitelist.
      'http://solution/2' => [
        'rid' => 'solution',
        'solution_to_version' => [
          'http://version/2',
        ],
        'solution_to_distro' => [
          'http://distro/1',
          // Not whitelisted as is referred only by a blacklisted solution.
          'http://distro/3',
        ],
        'solution_to_solution' => [
          'http://solution/1',
        ],
      ],
      'http://version/1' => [
        'rid' => 'version',
        'version_to_distro' => [
          'http://distro/1',
          'http://distro/2',
          // On the path http://solution/2 > http://version/2 > http://distro/5,
          // is blacklisted but is also referred by the whitelisted path:
          // http://solution/1 > http://version/1 > http://distro/5.
          'http://distro/5',
        ],
        'version_to_solution' => [
          // Circular, but it doesn't hurt.
          'http://solution/1',
        ],
      ],
      'http://version/2' => [
        'rid' => 'version',
        'version_to_distro' => [
          'http://distro/4',
        ],
      ],
      'http://distro/1' => ['rid' => 'distro'],
      'http://distro/2' => ['rid' => 'distro'],
      'http://distro/3' => ['rid' => 'distro'],
      'http://distro/4' => ['rid' => 'distro'],
      'http://distro/5' => ['rid' => 'distro'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('rdf_entity');
    $storage->delete($storage->loadMultiple(array_keys(static::getEntityData())));
    parent::tearDown();
  }

}
