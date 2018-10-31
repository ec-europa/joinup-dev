<?php

/**
 * @file
 * Contains \JoinupNewsletterSubContext.
 */

declare(strict_types = 1);

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\TraversingTrait;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for testing the newsletter integration.
 */
class JoinupNewsletterSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

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
