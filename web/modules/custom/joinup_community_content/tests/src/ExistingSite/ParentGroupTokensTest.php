<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_community_content\ExistingSite;

use Drupal\Tests\joinup_core\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\rdf_entity\Traits\DrupalTestTraits\RdfEntityCreationTrait;
use Drupal\Tests\token\Functional\TokenTestTrait;

/**
 * Tests the community content parent URL tokens.
 */
class ParentGroupTokensTest extends JoinupExistingSiteTestBase {

  use NodeCreationTrait;
  use RdfEntityCreationTrait;
  use TokenTestTrait;

  /**
   * Tests tokens defined by joinup_community_content.
   */
  public function testParentGroupDefinedTokens(): void {
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

    $collection_entity = $this->coreCreateNode([
      'type' => 'news',
      'title' => $this->randomString(),
      'field_state' => 'validated',
      'og_audience' => $collection->id(),
    ]);

    $tokens = [
      'path-to-community-content' => $collection->toUrl()->toString(),
    ];
    $data = ['node' => $collection_entity];
    $this->assertTokens('node', $data, $tokens);

    $solution_entity = $this->coreCreateNode([
      'type' => 'news',
      'title' => $this->randomString(),
      'field_state' => 'validated',
      'og_audience' => $solution->id(),
    ]);

    $tokens = [
      // Internal URLs start with a '/' and end without one, so there is no need
      // for a glue character.
      'path-to-community-content' => $collection->toUrl()->toString() . $solution->toUrl()->toString(),
    ];
    $data = ['node' => $solution_entity];
    $this->assertTokens('node', $data, $tokens);
  }

}
