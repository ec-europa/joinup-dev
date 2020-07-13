<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\NodeTrait;

/**
 * Behat step definitions for testing news pages.
 */
class JoinupNewsContext extends RawDrupalContext {

  use NodeTrait;

  /**
   * Navigates to the canonical page display of a news page.
   *
   * @param string $title
   *   The name of the news page.
   *
   * @When I go to the :title news
   * @When I visit the :title news
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function visitNewsPage($title) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, 'news');
    $this->visitPath($node->toUrl()->toString());
  }

}
