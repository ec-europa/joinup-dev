<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\ExistingSite;

use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\Tests\rdf_entity\Traits\DrupalTestTraits\RdfEntityCreationTrait;
use Drupal\search_api\Entity\Index;
use Drupal\taxonomy\Entity\Vocabulary;
use weitzman\DrupalTestTraits\Entity\TaxonomyCreationTrait;

/**
 * Tests hierarchy indexing for the aggregated field 'Topic'.
 *
 * @group joinup_core
 */
class TopicHierarchyIndexingTest extends JoinupExistingSiteTestBase {

  use RdfEntityCreationTrait;
  use TaxonomyCreationTrait;

  /**
   * A list of terms used in the test.
   *
   * @var \Drupal\rdf_taxonomy\Entity\RdfTerm[]
   */
  protected $terms = [];

  /**
   * Tests indexing of parent terms for Topic aggregated field.
   */
  public function testParentTermsAggregatedIndexing(): void {
    $topic_vocabulary = Vocabulary::load('topic');
    $this->terms['parent'] = $this->createTerm($topic_vocabulary, [
      'name' => 'Parent',
    ]);
    $this->terms['child'] = $this->createTerm($topic_vocabulary, [
      'name' => 'Child',
      'parent' => $this->terms['parent']->id(),
    ]);
    $collection = $this->createRdfEntity([
      'rid' => 'collection',
      'field_ar_state' => 'validated',
      'field_topic' => $this->terms['child']->id(),
    ]);
    $index = Index::load('published');
    $index->indexItems();

    foreach ($this->terms as $term) {
      $query = $index->query();
      $query->addCondition('topic', $term->id());
      $results = $query->execute()->getResultItems();
      $this->assertCount(1, $results);
      $item = reset($results);
      $this->assertSame($item->getOriginalObject()->getEntity()->id(), $collection->id());
    }
  }

}
