<?php

/**
 * @file
 * Contains \NewsSubContext.
 */

declare(strict_types = 1);

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\NodeTrait;

/**
 * Behat step definitions for testing pledge pages.
 */
class EasmePledgeSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use NodeTrait;

  /**
   * Navigates to the canonical page display of a pledge page.
   *
   * @param string $title
   *   The name of the news page.
   *
   * @When I go to the :title pledge
   * @When I visit the :title pledge
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function visitNewsPage($title) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, 'pledge');
    $this->visitPath($node->toUrl()->toString());
  }

}
