<?php

namespace Drupal\Tests\rdf_entity\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\Tests\joinup_core\Kernel\JoinupKernelTestBase;

/**
 * Tests RDF entity caching.
 *
 * @group rdf_entity
 */
class RdfEntityCacheTest extends JoinupKernelTestBase {

  /**
   * Tests RDF entity cache tags.
   */
  public function testCacheTags() {
    /** @var \Drupal\Core\Cache\CacheBackendInterface $cache */
    $cache = \Drupal::cache();

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

  /**
   * Test if the cache is invalidated when editing an RDF entity through API.
   */
  public function testEntityCacheInvalidadtion() {
    // Create a rdf_entity.
    $rdf = Rdf::create([
      'rid' => 'dummy',
      'id' => 'http://example.com',
      'label' => 'Foo',
    ]);
    $rdf->save();

    // Check that the label is correct after reloading the entity.
    $rdf = Rdf::load($rdf->id());
    $this->assertEquals('Foo', $rdf->label());

    // Change the label.
    $rdf = Rdf::load($rdf->id());
    $rdf->label->value = 'Bar';
    $rdf->save();

    // Check that the label is correct after reloading the entity.
    $rdf = Rdf::load($rdf->id());
    $this->assertEquals('Bar', $rdf->label());
  }

}
