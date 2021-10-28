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
   * @var \DateTimeInterface
   */
  protected $now;

  /**
   * Tests outdated content.
   */
  public function testOutdatedContent(): void {
    \Drupal::configFactory()->getEditable('joinup_core.outdated_content_threshold')
      ->set('node.discussion', 3)
      ->set('node.document', 3)
      ->set('node.event', NULL)
      ->set('node.news', 10)
      ->save();

    $this->now = new \DateTime('now', new \DateTimeZone(date_default_timezone_get()));

    /** @var \Drupal\collection\Entity\CollectionInterface $collection */
    $collection = $this->createRdfEntity([
      'rid' => 'collection',
      'field_ar_state' => 'validated',
    ]);
    /** @var \Drupal\joinup_discussion\Entity\DiscussionInterface $discussion */
    $discussion = $this->createNode([
      'type' => 'discussion',
      'og_audience' => $collection,
      'published_at' => (clone $this->now)
        ->sub(new \DateInterval('P3YM'))
        ->sub(new \DateInterval('PT1M'))
        ->getTimestamp(),
    ]);
    /** @var \Drupal\joinup_document\Entity\DocumentInterface $document */
    $document = $this->createNode([
      'type' => 'document',
      'og_audience' => $collection,
      'published_at' => (clone $this->now)
        ->sub(new \DateInterval('P3Y'))
        ->add(new \DateInterval('PT1M'))
        ->getTimestamp(),
    ]);
    /** @var \Drupal\joinup_event\Entity\EventInterface $event */
    $event = $this->createNode([
      'type' => 'event',
      'og_audience' => $collection,
      'published_at' => (clone $this->now)
        ->sub(new \DateInterval('P40Y'))
        ->getTimestamp(),
    ]);
    /** @var \Drupal\joinup_news\Entity\NewsInterface $news */
    $news = $this->createNode([
      'type' => 'news',
      'og_audience' => $collection,
      'published_at' => (clone $this->now)
        ->sub(new \DateInterval('P5Y'))
        ->getTimestamp(),
    ]);

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
    $this->assertNotNull($entity->getOutdatedTime());
    $this->assertGreaterThan($this->getOutdatedTime($entity), $this->now);
  }

  /**
   * Asserts that a given entity is not outdated.
   *
   * @param \Drupal\joinup_core\Entity\OutdatedContentInterface $entity
   *   The entity.
   */
  protected function assertIsNotOutdated(OutdatedContentInterface $entity): void {
    if (!$entity->getOutdatedTime()) {
      // It never gets outdated.
      $this->assertTrue(TRUE);
      return;
    }
    $this->assertGreaterThan($this->now, $this->getOutdatedTime($entity));
  }

  /**
   * Returns the entity outdated date/time.
   *
   * @param \Drupal\joinup_core\Entity\OutdatedContentInterface $entity
   *   The entity.
   *
   * @return \DateTime|null
   *   The entity outdated date/time or NULL if it doesn't get outdated.
   */
  protected function getOutdatedTime(OutdatedContentInterface $entity): ?\DateTime {
    if ($entity->getOutdatedTime()) {
      return (new \DateTime("@{$entity->getOutdatedTime()}"))->setTimezone($this->now->getTimezone());
    }
    return NULL;
  }

}
