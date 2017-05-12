<?php

namespace Drupal\Tests\rdf_entity\Functional;

use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\Entity\RdfEntityType;

/**
 * RDF entity user interface test.
 */
class RdfEntityTest extends RdfWebTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['rdf_entity'];

  /**
   * Test if the cache is invalidated when editing an RDF entity through UI.
   */
  public function testCacheInvalidation() {
    $this->createRdfEntityType('some');
    $storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');

    $this->drupalLogin($this->rootUser);

    // Create a new RDF entity.
    $this->drupalPostForm('rdf_entity/add/some', ['label[0][value]' => 'Foo'], 'Save');

    // Load the created entity by label.
    $entities = $storage->loadByProperties(['label' => 'Foo', 'rid' => 'some']);
    $rdf = reset($entities);

    // Check that the entity has the correct label.
    $this->assertEquals('Foo', $rdf->label());

    // Edit the existing entity by changit its label.
    $this->drupalPostForm($rdf->toUrl('edit-form'), ['label[0][value]' => 'Bar'], 'Save');

    // Reload the entity using the API to see if cache has been invalidated.
    $rdf = Rdf::load($rdf->id());

    // Check that the fresh values were loaded.
    $this->assertEquals('Bar', $rdf->label());
  }

  /**
   * Creates a RDF bundle given a bundle ID.
   */
  protected function createRdfEntityType($rid) {
    $this->usedGraphs = [
      'default' => "http://example.com/$rid/published",
      'draft' => "http://example.com/$rid/draft",
    ];

    $third_party_settings = [
      'rdf_type' => "http://example.com/$rid",
      'mapping' => [
        'rid' => [
          'target_id' => [
            'predicate' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
            'format' => 'resource',
          ],
        ],
        'label' => [
          'value' => [
            'predicate' => "http://example.com/{$rid}_label",
            'format' => 'literal',
          ],
        ],
      ],
      'graph' => $this->usedGraphs,
    ];

    RdfEntityType::create([
      'rid' => $rid,
      'name' => $this->randomString(),
      'third_party_settings' => ['rdf_entity' => $third_party_settings],
    ])->save();
  }

}
