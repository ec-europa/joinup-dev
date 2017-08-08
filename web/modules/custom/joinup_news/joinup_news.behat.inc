<?php

/**
 * @file
 * Contains \NewsSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\NodeTrait;

/**
 * Behat step definitions for testing news pages.
 */
class NewsSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use NodeTrait;

  /**
   * Navigates to the canonical page display of a news page.
   *
   * @param string $title
   *   The name of the news page.
   *
   * @When (I )go to the :title news
   * @When (I )visit the :title news
   */
  public function visitNewsPage($title) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, 'news');
    $this->visitPath($node->url());
  }

}
