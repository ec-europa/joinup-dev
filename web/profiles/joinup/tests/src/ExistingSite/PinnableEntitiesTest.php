<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup\Kernel;

use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\collection\Entity\Collection;
use Drupal\collection\Entity\CollectionInterface;
use Drupal\joinup_group\Entity\PinnableGroupContentInterface;
use Drupal\joinup_news\Entity\News;
use Drupal\og\OgGroupAudienceHelperInterface;

/**
 * Tests pinnable entities.
 *
 * @group joinup
 */
class PinnableEntitiesTest extends JoinupExistingSiteTestBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Test getting groups for a pinned entity if the group has been deleted.
   *
   * This is a regression test for a fatal error that was occurring when content
   * was pinned in a community and the community was subsequently deleted. The
   * method was unexpectedly returning an array containing NULL values rather
   * than groups.
   */
  public function testGetGroupsWherePinnedWithDeletedGroup() {
    // Create a test community.
    $community = Collection::create();
    $community->setWorkflowState('validated')->save();

    // Create a test news article inside the community.
    /** @var \Drupal\joinup_news\Entity\NewsInterface $news */
    $news = News::create([
      'title' => $this->randomString(),
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $community->id(),
    ]);
    $news->save();

    // Pin the news article inside the community.
    $news->pin($community);

    // Check the groups where the entity is pinned. This should return an array
    // containing 1 single result: the test community.
    $result = $this->getGroupsWherePinned($news);
    $this->assertCount(1, $result);

    $pinned_group = reset($result);
    $this->assertInstanceOf(CollectionInterface::class, $pinned_group);
    $this->assertEquals($community->id(), $pinned_group->id());

    // Delete the community.
    $community->delete();

    // Check the groups where the entity is pinned again. This should now
    // return an empty array.
    $result = $this->getGroupsWherePinned($news);
    $this->assertEquals([], $result);
  }

  /**
   * Returns the groups where the given entity has been pinned.
   *
   * @param \Drupal\joinup_group\Entity\PinnableGroupContentInterface $entity
   *   The entity for which to return the groups.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   The groups where the entity has been pinned.
   */
  protected function getGroupsWherePinned(PinnableGroupContentInterface $entity) {
    // Refresh the entity so that our test is not affected by static caches.
    $entity = $this->entityTypeManager->getStorage($entity->getEntityTypeId())->loadUnchanged($entity->id());
    $group_ids = $entity->getPinnedGroupIds();
    return $this->entityTypeManager->getStorage('rdf_entity')->loadMultiple($group_ids);
  }

}
