<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\TraversingTrait;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for testing the newsletter integration.
 */
class JoinupNewsletterContext extends RawDrupalContext {

  use TraversingTrait;

  /**
   * Checks that a newsletter subscription form is not present in the last tile.
   *
   * @Then I should not see the newsletter subscription form in the last tile
   */
  public function assertNoNewsletterSubscriptionFormPresentInFinalTile(): void {
    $tiles = $this->getTiles();
    if (empty($tiles)) {
      throw new \RuntimeException('There are no tiles on the page.');
    }

    end($tiles);
    Assert::assertNotEquals('Newsletter', key($tiles));
  }

  /**
   * Checks that a newsletter subscription form is present in the last tile.
   *
   * @Then I should see the newsletter subscription form in the last tile
   */
  public function assertNewsletterSubscriptionFormPresentInFinalTile(): void {
    $tiles = $this->getTiles();
    if (empty($tiles)) {
      throw new \RuntimeException('There are no tiles on the page.');
    }

    end($tiles);
    Assert::assertEquals('Newsletter', key($tiles));
  }

}
