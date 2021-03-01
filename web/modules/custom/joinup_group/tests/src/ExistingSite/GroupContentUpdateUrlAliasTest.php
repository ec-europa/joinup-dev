<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_group\ExistingSite;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\Tests\rdf_entity\Traits\DrupalTestTraits\RdfEntityCreationTrait;
use weitzman\LoginTrait\LoginTrait;

/**
 * Tests group content URL aliases update when the group short ID changes.
 *
 * @group joinup_group
 *
 * @coversDefaultClass \Drupal\joinup_group\Entity\GroupTrait
 */
class GroupContentUpdateUrlAliasTest extends JoinupExistingSiteTestBase {

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
   *
   * @covers ::getGroupContentIds
   */
  public function testGroupContentUpdateUrlAlias(): void {
    $this->createContent();

    // Check that the collection content is correctly computed and predictable.
    $this->assertSame([
      // The creation order is determined, same for the node IDs order.
      'node' => [
        // Solutions nodes.
        $this->entity['node']['custom_page']['1.1']->id(),
        $this->entity['node']['discussion']['1.1']->id(),
        $this->entity['node']['document']['1.1']->id(),
        $this->entity['node']['event']['1.1']->id(),
        $this->entity['node']['news']['1.1']->id(),
        $this->entity['node']['custom_page']['1.2']->id(),
        $this->entity['node']['discussion']['1.2']->id(),
        $this->entity['node']['document']['1.2']->id(),
        $this->entity['node']['event']['1.2']->id(),
        $this->entity['node']['news']['1.2']->id(),
        $this->entity['node']['custom_page']['2.1']->id(),
        $this->entity['node']['discussion']['2.1']->id(),
        $this->entity['node']['document']['2.1']->id(),
        $this->entity['node']['event']['2.1']->id(),
        $this->entity['node']['news']['2.1']->id(),
        $this->entity['node']['custom_page']['2.2']->id(),
        $this->entity['node']['discussion']['2.2']->id(),
        $this->entity['node']['document']['2.2']->id(),
        $this->entity['node']['event']['2.2']->id(),
        $this->entity['node']['news']['2.2']->id(),
        // Collection nodes.
        $this->entity['node']['custom_page'][1]->id(),
        $this->entity['node']['discussion'][1]->id(),
        $this->entity['node']['document'][1]->id(),
        $this->entity['node']['event'][1]->id(),
        $this->entity['node']['news'][1]->id(),
        $this->entity['node']['glossary'][1]->id(),
        $this->entity['node']['custom_page'][2]->id(),
        $this->entity['node']['discussion'][2]->id(),
        $this->entity['node']['document'][2]->id(),
        $this->entity['node']['event'][2]->id(),
        $this->entity['node']['news'][2]->id(),
        $this->entity['node']['glossary'][2]->id(),
      ],
      'rdf_entity' => [
        'http://distribution/1.1',
        'http://distribution/1.1.1',
        'http://distribution/1.1.2',
        'http://distribution/1.2',
        'http://distribution/1.2.1',
        'http://distribution/1.2.2',
        'http://distribution/2.1',
        'http://distribution/2.1.1',
        'http://distribution/2.1.2',
        'http://distribution/2.2',
        'http://distribution/2.2.1',
        'http://distribution/2.2.2',
        'http://release/1.1',
        'http://release/1.2',
        'http://release/2.1',
        'http://release/2.2',
        'http://solution/1',
        'http://solution/2',
      ],
    ], $this->entity['rdf_entity']['collection'][0]->getGroupContentIds());

    // Check that the solution content is correctly computed and predictable.
    $this->assertSame([
      'node' => [
        $this->entity['node']['custom_page']['1.1']->id(),
        $this->entity['node']['discussion']['1.1']->id(),
        $this->entity['node']['document']['1.1']->id(),
        $this->entity['node']['event']['1.1']->id(),
        $this->entity['node']['news']['1.1']->id(),
        $this->entity['node']['custom_page']['1.2']->id(),
        $this->entity['node']['discussion']['1.2']->id(),
        $this->entity['node']['document']['1.2']->id(),
        $this->entity['node']['event']['1.2']->id(),
        $this->entity['node']['news']['1.2']->id(),
      ],
      'rdf_entity' => [
        'http://distribution/1.1',
        'http://distribution/1.1.1',
        'http://distribution/1.1.2',
        'http://distribution/1.2',
        'http://distribution/1.2.1',
        'http://distribution/1.2.2',
        'http://release/1.1',
        'http://release/1.2',
      ],
    ], $this->entity['rdf_entity']['solution'][1]->getGroupContentIds());
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

    $this->entity['rdf_entity']['collection'][0] = $this->createRdfEntity([
      'rid' => 'collection',
      'id' => 'http://collection',
      'label' => 'collection',
      'field_ar_state' => 'validated',
    ]);
    // Solutions.
    for ($i = 1; $i <= 2; $i++) {
      $this->entity['rdf_entity']['solution'][$i] = $this->createRdfEntity([
        'rid' => 'solution',
        'id' => "http://solution/{$i}",
        'label' => "solution {$i}",
        'collection' => 'http://collection',
        'field_is_state' => 'validated',
      ]);
      // Releases.
      for ($j = 1; $j <= 2; $j++) {
        $this->entity['rdf_entity']['asset_release']["{$i}.{$j}"] = $this->createRdfEntity([
          'rid' => 'asset_release',
          'id' => "http://release/{$i}.{$j}",
          'label' => "release {$i}.{$j}",
          'field_isr_is_version_of' => "http://solution/{$i}",
          'field_isr_state' => 'validated',
        ]);
        // Release distributions.
        for ($k = 1; $k <= 2; $k++) {
          $this->entity['rdf_entity']['asset_distribution']["{$i}.{$j}.{$k}"] = $this->createRdfEntity([
            'rid' => 'asset_distribution',
            'id' => "http://distribution/{$i}.{$j}.{$k}",
            'label' => "distribution {$i}.{$j}.{$k}",
            'parent' => "http://release/{$i}.{$j}",
          ]);
        }
      }
      // Solution standalone distributions.
      for ($j = 1; $j <= 2; $j++) {
        $this->entity['rdf_entity']['asset_distribution']["{$i}.{$j}"] = $this->createRdfEntity([
          'rid' => 'asset_distribution',
          'id' => "http://distribution/{$i}.{$j}",
          'label' => "distribution {$i}.{$j}",
          'parent' => "http://solution/{$i}",
        ]);
      }
      // Solution nodes.
      for ($j = 1; $j <= 2; $j++) {
        foreach ($solution_node_bundles as $bundle) {
          $this->entity['node'][$bundle]["{$i}.{$j}"] = $this->createNode([
            'type' => $bundle,
            'title' => "{$bundle} {$i}.{$j}",
            'og_audience' => "http://solution/{$i}",
            'field_state' => 'validated',
          ]);
        }
      }
    }
    // Collection nodes.
    for ($i = 1; $i <= 2; $i++) {
      foreach ($collection_node_bundles as $bundle) {
        $this->entity['node'][$bundle][$i] = $this->createNode([
          'type' => $bundle,
          'title' => "{$bundle} {$i}",
          'og_audience' => 'http://collection',
          'field_state' => 'validated',
        ]);
      }
    }

    // Reload all entities to refresh the entity reference fields.
    array_walk($this->entity, function (array $bundles, string $entity_type_id): void {
      array_walk($bundles, function (array $entities, string $bundle) use ($entity_type_id): void {
        array_walk($entities, function (ContentEntityInterface $entity, string $index) use ($entity_type_id, $bundle): void {
          $this->entity[$entity_type_id][$bundle][$index] = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId())->load($entity->id());
        });
      });
    });
  }

}
