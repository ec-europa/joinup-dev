<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup\Kernel;

use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\collection\Entity\Collection;
use Drupal\collection\Entity\CollectionInterface;
use Drupal\joinup_group\Entity\GroupContentInterface;
use Drupal\joinup_news\Entity\News;
use Drupal\og\OgGroupAudienceHelperInterface;

/**
 * Tests for the PinService service.
 *
 * @coversDefaultClass \Drupal\joinup\PinService
 * @group joinup
 */
class PinServiceTest extends JoinupExistingSiteTestBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The service that handles pinned entities. This is the system under test.
   *
   * @var \Drupal\joinup\PinServiceInterface
   */
  protected $pinService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->pinService = $this->container->get('joinup.pin_service');
  }

  /**
   * Test getting groups for a pinned entity if the group has been deleted.
   *
   * This is a regression test for a fatal error that was occurring when content
   * was pinned in a collection and the collection was subsequently deleted. The
   * method was unexpectedly returning an array containing NULL values rather
   * than groups.
   *
   * @covers ::getGroupsWherePinned
   */
  public function testGetGroupsWherePinnedWithDeletedGroup() {
    // Create a test collection.
    $collection = Collection::create();
    $collection->setWorkflowState('validated')->save();

    // Create a test news article inside the collection.
    $news = News::create([
      'title' => $this->randomString(),
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $collection->id(),
    ]);
    $news->save();

    // Pin the news article inside the collection.
    $this->pinService->setEntityPinned($news, $collection, TRUE);

    // Ask the PinService for the groups where the entity is pinned. This should
    // return an array containing 1 single result: the test collection.
    $result = $this->getGroupsWherePinned($news);
    $this->assertCount(1, $result);

    $pinned_group = reset($result);
    $this->assertInstanceOf(CollectionInterface::class, $pinned_group);
    $this->assertEquals($collection->id(), $pinned_group->id());

    // Delete the collection.
    $collection->delete();

    // Ask the PinService again for the groups where the entity is pinned. This
    // should now return an empty array.
    $result = $this->getGroupsWherePinned($news);
    $this->assertEquals([], $result);
  }

  /**
   * Returns the groups where the given entity has been pinned.
   *
   * @param \Drupal\joinup_group\Entity\GroupContentInterface $entity
   *   The entity for which to return the groups.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   The groups where the entity has been pinned.
   */
  protected function getGroupsWherePinned(GroupContentInterface $entity) {
    // Refresh the entity so that our test is not affected by static caches.
    $entity = $this->entityTypeManager->getStorage($entity->getEntityTypeId())->loadUnchanged($entity->id());
    return $this->pinService->getGroupsWherePinned($entity);
  }

}
