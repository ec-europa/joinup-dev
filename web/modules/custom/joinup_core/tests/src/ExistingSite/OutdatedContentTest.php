<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\ExistingSite;

use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\Tests\rdf_entity\Traits\DrupalTestTraits\RdfEntityCreationTrait;
use Drupal\joinup_core\Entity\OutdatedContentInterface;

/**
 * Tests outdated content.
 *
 * @group joinup_core
 */
class OutdatedContentTest extends JoinupExistingSiteTestBase {

  use RdfEntityCreationTrait;

  /**
   * The current time.
   *
   * @var int
   */
  protected $now;

  /**
   * Tests outdated content.
   */
  public function testOutdatedContent(): void {
    \Drupal::configFactory()->getEditable('joinup_core.outdated_content_threshold')
      ->set('rdf_entity.collection', 10)
      ->set('rdf_entity.solution', 7)
      ->set('node.discussion', 3)
      ->set('node.document', 3)
      ->set('node.event', NULL)
      ->set('node.news', 10)
      ->save();

    $this->now = time();

    /** @var \Drupal\collection\Entity\CollectionInterface $collection */
    $collection = $this->createRdfEntity([
      'rid' => 'collection',
      'created' => strtotime('-4 years', $this->now),
      'field_ar_state' => 'validated',
    ]);
    /** @var \Drupal\solution\Entity\SolutionInterface $solution */
    $solution = $this->createRdfEntity([
      'rid' => 'solution',
      'collection' => $collection,
      'created' => strtotime('-19 years', $this->now),
      'field_is_state' => 'validated',
    ]);
    /** @var \Drupal\joinup_discussion\Entity\DiscussionInterface $discussion */
    $discussion = $this->createNode([
      'type' => 'discussion',
      'og_audience' => $collection,
      'published_at' => strtotime('-3 years -1 minute', $this->now),
    ]);
    /** @var \Drupal\joinup_document\Entity\DocumentInterface $document */
    $document = $this->createNode([
      'type' => 'document',
      'og_audience' => $collection,
      'published_at' => strtotime('-3 years +1 minute', $this->now),
    ]);
    /** @var \Drupal\joinup_event\Entity\EventInterface $event */
    $event = $this->createNode([
      'type' => 'event',
      'og_audience' => $collection,
      'published_at' => strtotime('-40 years', $this->now),
    ]);
    /** @var \Drupal\joinup_news\Entity\NewsInterface $news */
    $news = $this->createNode([
      'type' => 'news',
      'og_audience' => $collection,
      'published_at' => strtotime('-5 years', $this->now),
    ]);

    // The collection is not outdated.
    $this->assertIsNotOutdated($collection);
    // The solution is outdated.
    $this->assertIsOutdated($solution);
    // The discussion is outdated.
    $this->assertIsOutdated($discussion);
    // The document is not outdated.
    $this->assertIsNotOutdated($document);
    // The event is never outdated.
    $this->assertIsNotOutdated($event);
    // The news item is not outdated.
    $this->assertIsNotOutdated($news);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    \Drupal::configFactory()->getEditable('joinup_core.outdated_content_threshold')
      ->setData([])
      ->save();
    parent::tearDown();
  }

  /**
   * Asserts that a given entity is outdated.
   *
   * @param \Drupal\joinup_core\Entity\OutdatedContentInterface $entity
   *   The entity.
   */
  protected function assertIsOutdated(OutdatedContentInterface $entity): void {
    $outdated_time = $entity->getOutdatedTime();
    $this->assertNotNull($outdated_time);
    $this->assertGreaterThan($outdated_time, $this->now);
  }

  /**
   * Asserts that a given entity is not outdated.
   *
   * @param \Drupal\joinup_core\Entity\OutdatedContentInterface $entity
   *   The entity.
   */
  protected function assertIsNotOutdated(OutdatedContentInterface $entity): void {
    $outdated_time = $entity->getOutdatedTime();
    $this->assertTrue(!$outdated_time || ($this->now < $outdated_time));
  }

}
