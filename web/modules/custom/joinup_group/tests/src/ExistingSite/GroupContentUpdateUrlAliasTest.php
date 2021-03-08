<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_group\ExistingSite;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\Tests\rdf_entity\Traits\DrupalTestTraits\RdfEntityCreationTrait;
use Drupal\rdf_taxonomy\Entity\RdfTerm;
use Drush\TestTraits\DrushTestTrait;
use weitzman\LoginTrait\LoginTrait;

/**
 * Tests group content URL aliases update when the group short ID changes.
 *
 * @group joinup_group
 */
class GroupContentUpdateUrlAliasTest extends JoinupExistingSiteTestBase {

  use DrushTestTrait;
  use LoginTrait;
  use RdfEntityCreationTrait;

  /**
   * Testing entities.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface[][][]
   */
  protected $entity = [];

  /**
   * Tests group content URL aliases update when the group short ID changes.
   */
  public function testGroupContentUpdateUrlAlias(): void {
    $assert = $this->assertSession();

    $this->createContent();

    // Check that the collection content is correctly computed and predictable.
    $this->assertSame([
      // The creation order is determined, same is the node IDs order.
      'node' => [
        'custom_page' => [
          (int) $this->entity['node']['custom_page']['1.1']->id(),
          (int) $this->entity['node']['custom_page']['1.2']->id(),
          (int) $this->entity['node']['custom_page']['1']->id(),
          (int) $this->entity['node']['custom_page']['2']->id(),
        ],
        'discussion' => [
          (int) $this->entity['node']['discussion']['1.1']->id(),
          (int) $this->entity['node']['discussion']['1.2']->id(),
          (int) $this->entity['node']['discussion']['1']->id(),
          (int) $this->entity['node']['discussion']['2']->id(),
        ],
        'document' => [
          (int) $this->entity['node']['document']['1.1']->id(),
          (int) $this->entity['node']['document']['1.2']->id(),
          (int) $this->entity['node']['document']['1']->id(),
          (int) $this->entity['node']['document']['2']->id(),
        ],
        'event' => [
          (int) $this->entity['node']['event']['1.1']->id(),
          (int) $this->entity['node']['event']['1.2']->id(),
          (int) $this->entity['node']['event']['1']->id(),
          (int) $this->entity['node']['event']['2']->id(),
        ],
        'glossary' => [
          (int) $this->entity['node']['glossary']['1']->id(),
          (int) $this->entity['node']['glossary']['2']->id(),
        ],
        'news' => [
          (int) $this->entity['node']['news']['1.1']->id(),
          (int) $this->entity['node']['news']['1.2']->id(),
          (int) $this->entity['node']['news']['1']->id(),
          (int) $this->entity['node']['news']['2']->id(),
        ],
      ],
      'rdf_entity' => [
        'asset_distribution' => [
          'http://distribution/1.1',
          'http://distribution/1.1.1',
          'http://distribution/1.1.2',
          'http://distribution/1.2',
          'http://distribution/1.2.1',
          'http://distribution/1.2.2',
        ],
        'asset_release' => [
          'http://release/1.1',
          'http://release/1.2',
        ],
        'solution' => [
          'http://solution/1',
        ],
      ],
    ], $this->entity['rdf_entity']['collection']['1']->getGroupContentIds());

    // Check that the solution content is correctly computed and predictable.
    $this->assertSame([
      'node' => [
        'custom_page' => [
          (int) $this->entity['node']['custom_page']['1.1']->id(),
          (int) $this->entity['node']['custom_page']['1.2']->id(),
        ],
        'discussion' => [
          (int) $this->entity['node']['discussion']['1.1']->id(),
          (int) $this->entity['node']['discussion']['1.2']->id(),
        ],
        'document' => [
          (int) $this->entity['node']['document']['1.1']->id(),
          (int) $this->entity['node']['document']['1.2']->id(),
        ],
        'event' => [
          (int) $this->entity['node']['event']['1.1']->id(),
          (int) $this->entity['node']['event']['1.2']->id(),
        ],
        'news' => [
          (int) $this->entity['node']['news']['1.1']->id(),
          (int) $this->entity['node']['news']['1.2']->id(),
        ],
      ],
      'rdf_entity' => [
        'asset_distribution' => [
          'http://distribution/1.1',
          'http://distribution/1.1.1',
          'http://distribution/1.1.2',
          'http://distribution/1.2',
          'http://distribution/1.2.1',
          'http://distribution/1.2.2',
        ],
        'asset_release' => [
          'http://release/1.1',
          'http://release/1.2',
        ],
      ],
    ], $this->entity['rdf_entity']['solution']['1']->getGroupContentIds());

    $this->drupalLogin($this->createUser([], NULL, FALSE, [
      'roles' => ['moderator'],
    ]));

    // Edit the solution and add a short ID.
    $edit = ['field_short_id[0][value]' => 'abc123'];
    $this->drupalPostForm($this->entity['rdf_entity']['solution']['1']->toUrl('edit-form'), $edit, 'Publish');
    $this->assertSession()->pageTextContains("You've added a solution short ID: abc123. It will take some time until the solution content URLs will be updated.");

    // Process both queues.
    $this->drush('queue:run', ['joinup_group:group_update']);
    $this->drush('queue:run', ['joinup_group:group_content_update']);

    // Check that solution content URL aliases were updated.
    $this->drupalGet($this->entity['rdf_entity']['solution']['1']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123');
    // Releases.
    $this->drupalGet($this->entity['rdf_entity']['asset_release']['1.1']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/release/11');
    $this->drupalGet($this->entity['rdf_entity']['asset_release']['1.2']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/release/12');
    // Release 1.1 distributions.
    $this->drupalGet($this->entity['rdf_entity']['asset_distribution']['1.1.1']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/distribution/distribution-111');
    $this->drupalGet($this->entity['rdf_entity']['asset_distribution']['1.1.2']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/distribution/distribution-112');
    // Release 1.2 distributions.
    $this->drupalGet($this->entity['rdf_entity']['asset_distribution']['1.2.1']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/distribution/distribution-121');
    $this->drupalGet($this->entity['rdf_entity']['asset_distribution']['1.2.2']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/distribution/distribution-122');
    // Solution distributions.
    $this->drupalGet($this->entity['rdf_entity']['asset_distribution']['1.1']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/distribution/distribution-11');
    $this->drupalGet($this->entity['rdf_entity']['asset_distribution']['1.2']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/distribution/distribution-12');
    // Solution nodes.
    $this->drupalGet($this->entity['node']['custom_page']['1.1']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/custompage-11');
    $this->drupalGet($this->entity['node']['custom_page']['1.2']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/custompage-12');
    $this->drupalGet($this->entity['node']['discussion']['1.1']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/discussion/discussion-11');
    $this->drupalGet($this->entity['node']['discussion']['1.2']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/discussion/discussion-12');
    $this->drupalGet($this->entity['node']['document']['1.1']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/document/document-11');
    $this->drupalGet($this->entity['node']['document']['1.2']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/document/document-12');
    $this->drupalGet($this->entity['node']['event']['1.1']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/event/event-11');
    $this->drupalGet($this->entity['node']['event']['1.2']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/event/event-12');
    $this->drupalGet($this->entity['node']['news']['1.1']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/news/news-11');
    $this->drupalGet($this->entity['node']['news']['1.2']->toUrl());
    $assert->addressEquals('/collection/collection/solution/abc123/news/news-12');

    // Edit the the collection.
    $edit = ['field_short_id[0][value]' => 'xyz789'];
    $this->drupalPostForm($this->entity['rdf_entity']['collection']['1']->toUrl('edit-form'), $edit, 'Publish');
    $this->assertSession()->pageTextContains("You've added a collection short ID: xyz789. It will take some time until the collection content URLs will be updated.");

    // Process both queues.
    $this->drush('queue:run', ['joinup_group:group_update']);
    $this->drush('queue:run', ['joinup_group:group_content_update']);

    // Check that group content URL aliases were updated.
    $this->drupalGet($this->entity['rdf_entity']['collection']['1']->toUrl());
    $assert->addressEquals('/collection/xyz789');
    // Solution.
    $this->drupalGet($this->entity['rdf_entity']['solution']['1']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123');
    // Releases.
    $this->drupalGet($this->entity['rdf_entity']['asset_release']['1.1']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/release/11');
    $this->drupalGet($this->entity['rdf_entity']['asset_release']['1.2']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/release/12');
    // Release 1.1 distributions.
    $this->drupalGet($this->entity['rdf_entity']['asset_distribution']['1.1.1']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/distribution/distribution-111');
    $this->drupalGet($this->entity['rdf_entity']['asset_distribution']['1.1.2']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/distribution/distribution-112');
    // Release 1.2 distributions.
    $this->drupalGet($this->entity['rdf_entity']['asset_distribution']['1.2.1']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/distribution/distribution-121');
    $this->drupalGet($this->entity['rdf_entity']['asset_distribution']['1.2.2']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/distribution/distribution-122');
    // Solution distributions.
    $this->drupalGet($this->entity['rdf_entity']['asset_distribution']['1.1']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/distribution/distribution-11');
    $this->drupalGet($this->entity['rdf_entity']['asset_distribution']['1.2']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/distribution/distribution-12');
    // Solution nodes.
    $this->drupalGet($this->entity['node']['custom_page']['1.1']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/custompage-11');
    $this->drupalGet($this->entity['node']['custom_page']['1.2']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/custompage-12');
    $this->drupalGet($this->entity['node']['discussion']['1.1']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/discussion/discussion-11');
    $this->drupalGet($this->entity['node']['discussion']['1.2']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/discussion/discussion-12');
    $this->drupalGet($this->entity['node']['document']['1.1']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/document/document-11');
    $this->drupalGet($this->entity['node']['document']['1.2']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/document/document-12');
    $this->drupalGet($this->entity['node']['event']['1.1']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/event/event-11');
    $this->drupalGet($this->entity['node']['event']['1.2']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/event/event-12');
    $this->drupalGet($this->entity['node']['news']['1.1']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/news/news-11');
    $this->drupalGet($this->entity['node']['news']['1.2']->toUrl());
    $assert->addressEquals('/collection/xyz789/solution/abc123/news/news-12');
    $this->drupalGet($this->entity['node']['custom_page']['1']->toUrl());
    // Collection's nodes.
    $assert->addressEquals('/collection/xyz789/custompage-1');
    $this->drupalGet($this->entity['node']['custom_page']['2']->toUrl());
    $assert->addressEquals('/collection/xyz789/custompage-2');
    $this->drupalGet($this->entity['node']['discussion']['1']->toUrl());
    $assert->addressEquals('/collection/xyz789/discussion/discussion-1');
    $this->drupalGet($this->entity['node']['discussion']['2']->toUrl());
    $assert->addressEquals('/collection/xyz789/discussion/discussion-2');
    $this->drupalGet($this->entity['node']['document']['1']->toUrl());
    $assert->addressEquals('/collection/xyz789/document/document-1');
    $this->drupalGet($this->entity['node']['document']['2']->toUrl());
    $assert->addressEquals('/collection/xyz789/document/document-2');
    $this->drupalGet($this->entity['node']['event']['1']->toUrl());
    $assert->addressEquals('/collection/xyz789/event/event-1');
    $this->drupalGet($this->entity['node']['event']['2']->toUrl());
    $assert->addressEquals('/collection/xyz789/event/event-2');
    $this->drupalGet($this->entity['node']['glossary']['1']->toUrl());
    $assert->addressEquals('/collection/xyz789/glossary/term/glossary-1');
    $this->drupalGet($this->entity['node']['glossary']['2']->toUrl());
    $assert->addressEquals('/collection/xyz789/glossary/term/glossary-2');
    $this->drupalGet($this->entity['node']['news']['1']->toUrl());
    $assert->addressEquals('/collection/xyz789/news/news-1');
    $this->drupalGet($this->entity['node']['news']['2']->toUrl());
    $assert->addressEquals('/collection/xyz789/news/news-2');

    // Test the success message on short ID update.
    $edit = ['field_short_id[0][value]' => 'other-id'];
    $this->drupalPostForm($this->entity['rdf_entity']['solution']['1']->toUrl('edit-form'), $edit, 'Publish');
    $this->assertSession()->pageTextContains("You've changed the solution's short ID from abc123 to other-id. It will take some time until the solution content URLs will be updated.");
    $this->drupalPostForm($this->entity['rdf_entity']['collection']['1']->toUrl('edit-form'), $edit, 'Publish');
    $this->assertSession()->pageTextContains("You've changed the collection's short ID from xyz789 to other-id. It will take some time until the collection content URLs will be updated.");

    // Test the success message on short ID delete.
    $edit = ['field_short_id[0][value]' => ''];
    $this->drupalPostForm($this->entity['rdf_entity']['solution']['1']->toUrl('edit-form'), $edit, 'Publish');
    $this->assertSession()->pageTextContains("You've removed the solution's short ID: other-id. It will take some time until the solution content URLs will be updated.");
    $this->drupalPostForm($this->entity['rdf_entity']['collection']['1']->toUrl('edit-form'), $edit, 'Publish');
    $this->assertSession()->pageTextContains("You've removed the collection's short ID: other-id. It will take some time until the collection content URLs will be updated.");
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    $this->drush('queue:delete', ['joinup_group:group_update']);
    $this->drush('queue:delete', ['joinup_group:group_content_update']);
    parent::tearDown();
  }

  /**
   * Creates testing content.
   */
  protected function createContent(): void {
    $solution_node_bundles = [
      'custom_page',
      'discussion',
      'document',
      'event',
      'news',
    ];
    $collection_node_bundles = array_merge($solution_node_bundles, ['glossary']);

    $this->createRdfEntity([
      'id' => 'http://example.com/owner',
      'rid' => 'owner',
      'field_owner_name' => $this->randomString(),
      'field_owner_type' => 'http://purl.org/adms/publishertype/SupraNationalAuthority',
    ]);
    $this->createRdfEntity([
      'id' => 'http://example.com/contact',
      'rid' => 'contact_information',
      'field_ci_name' => $this->randomString(),
      'field_ci_email' => $this->randomMachineName() . '@example.com',
    ]);

    $parent = RdfTerm::create([
      'vid' => 'policy_domain',
      'tid' => 'http://example.com/policy/parent',
      'name' => $this->randomString(),
    ]);
    $parent->save();
    $this->markEntityForCleanup($parent);
    $term = RdfTerm::create([
      'vid' => 'policy_domain',
      'tid' => 'http://example.com/policy',
      'name' => $this->randomString(),
      'parent' => 'http://example.com/policy/parent',
    ]);
    $term->save();
    $this->markEntityForCleanup($term);

    $this->entity['rdf_entity']['collection']['1'] = $this->createRdfEntity([
      'rid' => 'collection',
      'id' => 'http://collection',
      'label' => 'collection',
      'field_ar_description' => $this->randomString(),
      'field_ar_state' => 'validated',
      'field_ar_owner' => 'http://example.com/owner',
      'field_policy_domain' => 'http://example.com/policy',
      'field_ar_contact_information' => 'http://example.com/contact',
    ]);

    $this->entity['rdf_entity']['solution']['1'] = $this->createRdfEntity([
      'rid' => 'solution',
      'id' => "http://solution/1",
      'label' => "solution 1",
      'collection' => 'http://collection',
      'field_is_description' => $this->randomString(),
      'field_is_state' => 'validated',
      'field_is_owner' => 'http://example.com/owner',
      'field_policy_domain' => 'http://example.com/policy',
      'field_is_solution_type' => 'http://data.europa.eu/dr8/ArchitectureBuildingBlock',
      'field_is_contact_information' => 'http://example.com/contact',
    ]);
    // Releases.
    for ($i = 1; $i <= 2; $i++) {
      $this->entity['rdf_entity']['asset_release']["1.{$i}"] = $this->createRdfEntity([
        'rid' => 'asset_release',
        'id' => "http://release/1.{$i}",
        'label' => "release 1.{$i}",
        'field_isr_release_number' => "1.{$i}",
        'field_isr_is_version_of' => 'http://solution/1',
        'field_isr_state' => 'validated',
      ]);
      // Release distributions.
      for ($j = 1; $j <= 2; $j++) {
        $this->entity['rdf_entity']['asset_distribution']["1.{$i}.{$j}"] = $this->createRdfEntity([
          'rid' => 'asset_distribution',
          'id' => "http://distribution/1.{$i}.{$j}",
          'label' => "distribution 1.{$i}.{$j}",
          'parent' => "http://release/1.{$i}",
        ]);
      }
    }
    // Solution standalone distributions.
    for ($i = 1; $i <= 2; $i++) {
      $this->entity['rdf_entity']['asset_distribution']["1.{$i}"] = $this->createRdfEntity([
        'rid' => 'asset_distribution',
        'id' => "http://distribution/1.{$i}",
        'label' => "distribution 1.{$i}",
        'parent' => 'http://solution/1',
      ]);
    }
    // Solution nodes.
    for ($i = 1; $i <= 2; $i++) {
      foreach ($solution_node_bundles as $bundle) {
        $this->entity['node'][$bundle]["1.{$i}"] = $this->createNode([
          'type' => $bundle,
          'title' => "{$bundle} 1.{$i}",
          'og_audience' => 'http://solution/1',
          'field_state' => 'validated',
        ]);
      }
    }
    // Collection nodes.
    for ($i = 1; $i <= 2; $i++) {
      foreach ($collection_node_bundles as $bundle) {
        $this->entity['node'][$bundle]["${i}"] = $this->createNode([
          'type' => $bundle,
          'title' => "{$bundle} {$i}",
          'og_audience' => 'http://collection',
          'field_state' => 'validated',
        ]);
      }
    }

    $this->reloadEntities();
  }

  /**
   * Reload all testing entities.
   *
   * Used to refresh the entity reference fields.
   */
  protected function reloadEntities(): void {
    array_walk($this->entity, function (array $bundles, string $entity_type_id): void {
      array_walk($bundles, function (array $entities, string $bundle) use ($entity_type_id): void {
        array_walk($entities, function (ContentEntityInterface $entity, string $index) use ($entity_type_id, $bundle): void {
          $this->entity[$entity_type_id][$bundle][$index] = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId())->load($entity->id());
        });
      });
    });
  }

}
