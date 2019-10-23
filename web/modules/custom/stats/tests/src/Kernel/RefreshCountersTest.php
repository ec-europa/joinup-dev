<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_stats_test\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Tests the download & visit count refresh.
 *
 * @group joinup_stats
 */
class RefreshCountersTest extends KernelTestBase {

  use SparqlConnectionTrait;

  /**
   * Testing entities.
   *
   * @var array
   */
  protected $entities = [];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'cached_computed_field',
    'dynamic_entity_reference',
    'field',
    'file',
    'file_url',
    'joinup_stats',
    'joinup_stats_test',
    'matomo_reporting_api',
    'meta_entity',
    'node',
    'og',
    'rdf_entity',
    'sparql_entity_storage',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpSparql();

    $this->installEntitySchema('meta_entity');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig([
      'cached_computed_field',
      'joinup_stats',
      'joinup_stats_test',
      'meta_entity',
      'sparql_entity_storage',
    ]);

    // Make fields to instantly require refresh for testing purposes.
    FieldConfig::loadByName('meta_entity', 'download_count', 'count')->setSetting('cache-max-age', 0)->save();
    FieldConfig::loadByName('meta_entity', 'visit_count', 'count')->setSetting('cache-max-age', 0)->save();

    $this->createContent();
  }

  /**
   * Tests download & visit count refresh.
   */
  public function testRefreshCounters(): void {
    $queue = $this->container->get('cached_computed_field.manager')->getQueue();

    // Check that creating entities the meta entities are also created. Only
    // test one RDF entity and one node.
    $distro = $this->entities['rdf_entity']['http://example.com/distro/2'];
    $this->assertSame('http://example.com/distro/2', $distro->download_count->entity->target->target_id);
    $node = $this->entities['node'][3];
    $this->assertEquals(3, $node->visit_count->entity->target->target_id);

    // Check that before first cron run the queue is empty.
    $this->assertEquals(0, $queue->numberOfItems());

    // Run cron for the first time.
    cached_computed_field_cron();

    // Check that after first cron run all entities were queued.
    $this->assertEquals(7, $queue->numberOfItems());

    // Second cron run.
    cached_computed_field_cron();

    // Check that after second cron the queue has been consumed.
    $this->assertEquals(0, $queue->numberOfItems());

    // Check that the cached computed fields were updated.
    // @see \Drupal\joinup_stats_test\Mocks\TestQuery::getMockedResponseArray()
    $this->assertEquals(55, $this->entities['rdf_entity']['http://example.com/distro/1']->download_count->entity->count->value);
    $this->assertEquals(2034, $this->entities['rdf_entity']['http://example.com/distro/2']->download_count->entity->count->value);
    $this->assertEquals(0, $this->entities['rdf_entity']['http://example.com/distro/3']->download_count->entity->count->value);
    $this->assertEquals(3846545, $this->entities['node'][1]->visit_count->entity->count->value);
    $this->assertEquals(234, $this->entities['node'][2]->visit_count->entity->count->value);
    $this->assertEquals(8766, $this->entities['node'][3]->visit_count->entity->count->value);
    $this->assertEquals(334, $this->entities['node'][4]->visit_count->entity->count->value);

    // Pretend that the stats were incremented on Matomo side.
    $this->container->get('state')->set('joinup_stats_test.increment', [
      'http://example.com/distro/1' => 1000,
      'http://example.com/distro/2' => 10000,
      'http://example.com/distro/3' => 39,
      '1' => 1000000,
      '2' => 10000,
      '3' => 20000,
      '4' => 200000,
    ]);
    sleep(1);

    // On third cron run the queue should be repopulated.
    cached_computed_field_cron();
    $this->assertEquals(7, $queue->numberOfItems());

    // Run again the cron and check that values were incremented. Entities need
    // reload in order to get the new values.
    cached_computed_field_cron();
    $entity_type_manager = $this->container->get('entity_type.manager');
    foreach ($this->entities as $entity_type_id => $entities) {
      foreach ($entities as $entity) {
        $this->entities[$entity_type_id][$entity->id()] = $entity_type_manager->getStorage($entity_type_id)->load($entity->id());
      }
    }
    $this->assertEquals(1055, $this->entities['rdf_entity']['http://example.com/distro/1']->download_count->entity->count->value);
    $this->assertEquals(12034, $this->entities['rdf_entity']['http://example.com/distro/2']->download_count->entity->count->value);
    $this->assertEquals(39, $this->entities['rdf_entity']['http://example.com/distro/3']->download_count->entity->count->value);
    $this->assertEquals(4846545, $this->entities['node'][1]->visit_count->entity->count->value);
    $this->assertEquals(10234, $this->entities['node'][2]->visit_count->entity->count->value);
    $this->assertEquals(28766, $this->entities['node'][3]->visit_count->entity->count->value);
    $this->assertEquals(200334, $this->entities['node'][4]->visit_count->entity->count->value);
  }

  /**
   * Creates the test content.
   */
  protected function createContent(): void {
    $entity_type_manager = $this->container->get('entity_type.manager');
    foreach ($this->getContentData() as $entity_type_id => $items) {
      $storage = $entity_type_manager->getStorage($entity_type_id);
      foreach ($items as $values) {
        $entity = $storage->create($values);
        $entity->save();
        $this->entities[$entity_type_id][$entity->id()] = $entity;
      }
    }
  }

  /**
   * Returns test data for testing entities.
   *
   * @return array
   *   Test data.
   */
  protected function getContentData(): array {
    file_put_contents('public://d1', $this->randomString());
    file_put_contents('public://d3', $this->randomString());
    file_put_contents('public://d3', $this->randomString());

    return [
      'rdf_entity' => [
        [
          'id' => 'http://example.com/solution/1',
          'rid' => 'solution',
          'label' => 'Solution 1',
        ],
        [
          'id' => 'http://example.com/distro/1',
          'rid' => 'asset_distribution',
          'label' => 'Distro 1',
          'field_ad_access_url' => File::create([
            'uri' => 'public://d1',
          ]),
          'og_audience' => 'http://example.com/solution/1',
        ],
        [
          'id' => 'http://example.com/distro/2',
          'rid' => 'asset_distribution',
          'label' => 'Distro 2',
          'field_ad_access_url' => File::create([
            'uri' => 'public://d2',
          ]),
        ],
        [
          'id' => 'http://example.com/distro/3',
          'rid' => 'asset_distribution',
          'label' => 'Distro 3',
          'field_ad_access_url' => File::create([
            'uri' => 'public://d3',
          ]),
        ],
      ],
      // Enforce predictable NIDs.
      'node' => [
        [
          'type' => 'discussion',
          'nid' => 1,
          'title' => 'Discussion',
        ],
        [
          'type' => 'document',
          'nid' => 2,
          'title' => 'Document',
        ],
        [
          'type' => 'event',
          'nid' => 3,
          'title' => 'Event',
        ],
        [
          'type' => 'news',
          'nid' => 4,
          'title' => 'News',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $storage = $this->container->get('entity_type.manager')->getStorage('rdf_entity');
    $storage->delete($this->entities['rdf_entity']);
    parent::tearDown();
  }

}
