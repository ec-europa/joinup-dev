<?php

namespace Drupal\Tests\collection\ExistingSite;

use weitzman\DrupalTestTraits\Entity\NodeCreationTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test.
 *
 * @group collection
 */
class ETest extends ExistingSiteBase {

  use NodeCreationTrait;

  /**
   * Test.
   */
  public function test() {
    $n = $this->createNode([
      'title' => 'Page',
      'type' => 'custom_page',
    ]);

    $this->assertSame('Page', $n->label());
    $this->assertTrue(0);
  }

}
