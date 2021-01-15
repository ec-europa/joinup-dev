<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_group\ExistingSite;

use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\Tests\rdf_entity\Traits\DrupalTestTraits\RdfEntityCreationTrait;
use Drupal\Tests\token\Functional\TokenTestTrait;

/**
 * Tests the rdf entity tokens for solutions, releases and distributions.
 */
class GroupTokensTest extends JoinupExistingSiteTestBase {

  use RdfEntityCreationTrait;
  use TokenTestTrait;

  /**
   * Tests tokens defined by joinup_group.
   */
  public function testGroupDefinedTokens() {
    $collection = $this->createRdfEntity([
      'rid' => 'collection',
      'label' => $this->randomString(),
      'field_ar_state' => 'validated',
    ]);

    $solution = $this->createRdfEntity([
      'rid' => 'solution',
      'field_is_state' => 'validated',
      'label' => $this->randomString(),
      'collection' => $collection->id(),
    ]);

    $standalone_distribution = $this->createRdfEntity([
      'rid' => 'asset_distribution',
      'label' => $this->randomString(),
      'og_audience' => $solution->id(),
    ]);

    $release_distribution = $this->createRdfEntity([
      'rid' => 'asset_distribution',
      'label' => $this->randomString(),
      'og_audience' => $solution->id(),
    ]);

    $release = $this->createRdfEntity([
      'rid' => 'asset_release',
      'field_isr_is_version_of' => $solution->id(),
      'field_isr_distribution' => $release_distribution->id(),
    ]);

    $entities_to_test = [
      $solution,
      $release,
      $release_distribution,
      $standalone_distribution,
    ];

    $tokens = [
      'parent_collection' => $collection->label(),
      'parent_collection:id:value' => $collection->id(),
      'parent_collection:label:value' => $collection->label(),
      // URL::toString generates an aliased path.
      'parent_collection:url' => $collection->toUrl('canonical')->toString(),
    ];

    foreach ($entities_to_test as $entity) {
      $data = ['rdf_entity' => $entity];
      $this->assertTokens('rdf_entity', $data, $tokens);
    }
  }

}
