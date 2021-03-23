<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_entity\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Tests RDF entity caching.
 *
 * @group rdf_entity
 */
class RdfEntityCacheTest extends RdfKernelTestBase {

  /**
   * Tests RDF entity cache tags.
   */
  public function testCacheTags() {
    $cache = $this->container->get('cache.default');

    // Create a rdf_entity.
    $rdf = Rdf::create([
      'rid' => 'dummy',
      'id' => 'http://example.com',
      'label' => 'Foo',
      'field_text' => 'Bar',
    ]);
    $rdf->save();

    $tags = $rdf->getCacheTags();

    // Store some data in the cache, tagged with $rdf entity cache tags.
    $cache->set('foo', 'bar', Cache::PERMANENT, $tags);

    // Check that data was stored correctly.
    $this->assertEquals('bar', $cache->get('foo')->data);
    $this->assertSame($tags, $cache->get('foo')->tags);

    // Delete the entity.
    $rdf->delete();

    // Check that the cache has been invalidated.
    $this->assertFalse($cache->get('foo'));
  }

}
