<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_federation\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\pipeline\PipelineState;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\sparql_entity_storage\Entity\SparqlGraph;

/**
 * Tests the solution dependency builder from analyze_incoming_entities step.
 *
 * @coversDefaultClass \Drupal\joinup_federation\Plugin\pipeline\Step\AnalyzeIncomingEntities
 *
 * @group joinup_federation
 */
class AnalyzeIncomingEntitiesBuildSolutionDependencyTreeTest extends StepTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getUsedStepPlugins(): array {
    return ['analyze_incoming_entities' => []];
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
    $graph = Yaml::decode(file_get_contents(DRUPAL_ROOT . '/modules/contrib/sparql_entity_storage/config/install/sparql_entity_storage.graph.default.yml'));
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
   * @covers ::buildSolutionDependencyTree
   */
  public function testBuildSolutionDependencyTree(): void {
    $step_plugin = $this->pipeline->createStepInstance('analyze_incoming_entities');
    $state = (new PipelineState())->setStepId('analyze_incoming_entities');
    $state->setData(['solutionData', []]);
    $this->pipeline->setCurrentState($state);

    // Make AnalyzeIncomingEntities::buildSolutionDependencyTree() and
    // IncomingEntitiesDataHelperTrait::$solutionData accessible for test.
    $reflection = new \ReflectionClass($step_plugin);
    // Allow access to the ::buildSolutionDependencyTree.
    $solution_dependency_tree_method = $reflection->getMethod('buildSolutionDependencyTree');
    $solution_dependency_tree_method->setAccessible(TRUE);
    // Allow access to the ::addSolutionDataRoot.
    $add_solution_data_root_method = $reflection->getMethod('addSolutionDataRoot');
    $add_solution_data_root_method->setAccessible(TRUE);

    $solution_data_property = $reflection->getProperty('solutionData');
    $solution_data_property->setAccessible(TRUE);

    foreach ($this->getTestCases() as $solution_id => $expected_solution_data) {
      $add_solution_data_root_method->invokeArgs($step_plugin, [$solution_id]);
      $solution_dependency_tree_method->invokeArgs($step_plugin, [
        Rdf::load($solution_id),
        $solution_id,
      ]);
      $actual_data = $solution_data_property->getValue($step_plugin)[$solution_id];
      // Results order might differ between versions of Virtuoso. Ensure that
      // results are ordered properly before asserting.
      foreach ($actual_data['dependencies'] as &$bundle_dependencies) {
        ksort($bundle_dependencies);
      }
      $this->assertSame($expected_solution_data, $actual_data);
    }
  }

  /**
   * Returns data for creating testing entities.
   *
   * @return array[]
   *   Entity values.
   */
  protected static function getEntityData() {
    return [
      'http://solution/1' => [
        'rid' => 'solution',
        'solution_to_version' => [
          'http://version/1',
        ],
        'solution_to_distro' => [
          'http://distro/1',
        ],
        'solution_to_solution' => [
          'http://solution/2',
        ],
      ],
      'http://solution/2' => [
        'rid' => 'solution',
        'solution_to_version' => [
          'http://version/2',
        ],
        'solution_to_distro' => [
          'http://distro/1',
          'http://distro/3',
        ],
        'solution_to_solution' => [
          'http://solution/1',
        ],
      ],
      'http://version/1' => [
        'rid' => 'version',
        'version_to_distro' => [
          'http://distro/6',
          'http://distro/2',
          'http://distro/5',
        ],
        'version_to_solution' => [
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
      'http://distro/6' => ['rid' => 'distro'],
    ];
  }

  /**
   * Provides test cases for ::testBuildSolutionDependencyTree().
   *
   * @return array
   *   Test cases as an associative array keyed by the solution ID and having
   *   the expected solution data as values.
   *
   * @see \Drupal\Tests\joinup_federation\Kernel\AnalyzeIncomingEntitiesBuildSolutionDependencyTreeTest::testBuildSolutionDependencyTree()
   * @see \Drupal\joinup_federation\Plugin\pipeline\Step\IncomingEntitiesDataHelperTrait::$solutionData
   */
  protected function getTestCases() {
    return [
      'http://solution/1' => [
        'dependencies' => [
          'distro' => [
            'http://distro/1' => 'http://distro/1',
            'http://distro/2' => 'http://distro/2',
            'http://distro/5' => 'http://distro/5',
            'http://distro/6' => 'http://distro/6',
          ],
          'version' => [
            'http://version/1' => 'http://version/1',
          ],
        ],
      ],
      'http://solution/2' => [
        'dependencies' => [
          'distro' => [
            'http://distro/1' => 'http://distro/1',
            'http://distro/3' => 'http://distro/3',
            'http://distro/4' => 'http://distro/4',
          ],
          'version' => [
            'http://version/2' => 'http://version/2',
          ],
        ],
      ],
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
