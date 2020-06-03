<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\ExistingSite;

use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\rdf_entity\Traits\DrupalTestTraits\RdfEntityCreationTrait;
use Drupal\Tests\token\Functional\TokenTestTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Tests the community content parent URL tokens.
 */
class TokenTest extends JoinupExistingSiteTestBase {

  use NodeCreationTrait;
  use RdfEntityCreationTrait;
  use TokenTestTrait;

  /**
   * Tests the parent_collection token.
   */
  public function testParentCollection(): void {
    $collection = $this->createRdfEntity([
      'rid' => 'collection',
      'title' => $this->randomMachineName(),
      'field_ar_state' => 'validated',
    ]);

    $solution = $this->createRdfEntity([
      'rid' => 'solution',
      'title' => $this->randomMachineName(),
      'collection' => $collection,
      'field_is_state' => 'validated',
    ]);

    $asset_release = $this->createRdfEntity([
      'rid' => 'asset_release',
      'title' => $this->randomMachineName(),
      'field_isr_is_version_of' => $solution,
      'field_isr_state' => 'validated',
    ]);

    $asset_distribution = $this->createRdfEntity([
      'rid' => 'asset_distribution',
      'title' => $this->randomMachineName(),
      'og_audience' => $solution->id(),
    ]);

    // Check both the token itself and one of the recursively generated
    // tokens.
    $tokens = [
      'parent_collection' => $collection->label(),
      'parent_collection:rid:target_id' => 'collection',
    ];

    $data = ['rdf_entity' => $solution];
    $this->assertTokens('rdf_entity', $data, $tokens);

    $data = ['rdf_entity' => $asset_release];
    $this->assertTokens('rdf_entity', $data, $tokens);

    $data = ['rdf_entity' => $asset_distribution];
    $this->assertTokens('rdf_entity', $data, $tokens);
  }

  /**
   * Tests the 'short_id_or_title' token.
   *
   * @param string $bundle
   *   The entity bundle.
   * @param string $short_id
   *   The value of the short ID field.
   * @param string $title
   *   The title of the entity.
   * @param string $expected_token
   *   The expected token replacement for the 'short_id_or_title'.
   *
   * @dataProvider shortIdOrTitleDataProvider
   */
  public function testShortIdOrTitleToken(string $bundle, string $short_id, string $title, string $expected_token): void {
    $entity = Rdf::create([
      'rid' => $bundle,
      'label' => $title,
      'field_short_id' => $short_id,
    ]);

    $tokens = [
      'short_id_or_title' => $expected_token,
    ];
    $data = ['rdf_entity' => $entity];
    $this->assertTokens('rdf_entity', $data, $tokens);
  }

  /**
   * Tests that 'short_id_or_title' does not fail for bundles without short ID.
   */
  public function testShortIdOrTitleInvalidBundles(): void {
    $title = $this->randomMachineName();
    $entity = Rdf::create([
      'rid' => 'asset_release',
      'label' => $title,
    ]);

    $tokens = [
      'short_id_or_title' => $title,
    ];
    $data = ['rdf_entity' => $entity];
    $this->assertTokens('rdf_entity', $data, $tokens);
  }

  /**
   * Provides data for the testShortIdOrTitleToken method.
   */
  public function shortIdOrTitleDataProvider(): array {
    return [
      'solution with a short ID' => [
        'solution', 'some-short-id', 'some title', 'some-short-id',
      ],
      'solution without a short ID' => [
        'solution', '', 'some title', 'some title',
      ],
      'collection with a short ID' => [
        'collection', 'some-short-id', 'some title', 'some-short-id',
      ],
      'collection without a short ID' => [
        'collection', '', 'some title', 'some title',
      ],
    ];
  }

}
